<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\BigQueryService;
use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\BigQuery\Dataset;
use Google\Cloud\BigQuery\Table;
use Google\Cloud\Core\ExponentialBackoff;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BigQueryTestCommand extends Command
{
    protected static $defaultName = 'bigquery:test';

    /** @var BigQueryService */
    private $bigQueryService;

    /** @var ParameterBagInterface */
    private $params;

    /** @var string */
    private $projectId;

    public function __construct(BigQueryService $bigQueryService, ParameterBagInterface $params, string $name = null)
    {
        parent::__construct($name);

        $this->bigQueryService = $bigQueryService;
        $this->params = $params;
        $this->projectId = getenv('GOOGLE_PROJECT_ID');
    }

    protected function configure()
    {
        $this->addArgument('datasetId', InputArgument::REQUIRED, 'What dataset ID?')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $datasetId = $input->getArgument('datasetId');

        $output->writeln([
            'Big Query Testing',
            '============',
            '',
        ]);

        $client = $this->bigQueryService->getClient();

//        /** @var Dataset $d */
//        foreach($client->datasets() as $d) {
//            $d->delete(['deleteContents' => true]);
//        }

        $dataset = $this->createDataset($output, $client, $datasetId);

        /** @var Table $table */
        foreach ($dataset->tables() as $table) {
            $output->writeln([
                'Table found',
                $table->identity()['tableId'],
                '============',
                '',
            ]);
        }

        // QUERY
        $jobConfig = $client->query(sprintf('SELECT * FROM `%s.%s.images`', $this->projectId, $datasetId));
        $job = $client->startQuery($jobConfig);

        $backOff = new ExponentialBackoff(10);

        $backOff->execute(function () use ($job, $output) {
            $output->writeln(['Waiting for job to complete', '']);
            $job->reload();
            if (!$job->isComplete()) {
                $output->writeln('Job has not yet completed', 500);
            }
        });

        $queryResults = $job->queryResults();

        $rootPath = $this->params->get('kernel.project_dir');

        $fp = fopen(sprintf('%s/var/results.csv', $rootPath), 'w');

        foreach ($queryResults as $row) {
            fputcsv($fp, $row);
//            foreach ($row as $column => $value) {
//                $output->writeln(sprintf('%s: %s', $column, json_encode($value)));
//            }
        }

        $output->writeln('Results have been saved in /var/results.csv');

        fclose($fp);

        // CLEAN
//        $dataset->delete(['deleteContents' => true]);
//
//        $output->writeln('Tables and dataset have been deleted');

        return 0;
    }

    private function createDataset(OutputInterface $output, BigQueryClient $client, string $datasetId): Dataset
    {
        $dataset = $client->dataset($datasetId);

        if ($dataset->exists()) {
            return $dataset;
        }

        // CREATE DATASET AND TABLES
        $dataset =  $client->createDataset($datasetId);
        $fields1 = [
            [
                'name' => 'user_id',
                'type' => 'string',
                'mode' => 'required'
            ],
            [
                'name' => 'name',
                'type' => 'string'
            ],
        ];

        $fields2 = [
            [
                'name' => 'image_id',
                'type' => 'string',
                'mode' => 'required'
            ],
            [
                'name' => 'user_id',
                'type' => 'string',
                'mode' => 'required'
            ],
            [
                'name' => 'image_src',
                'type' => 'string',
                'mode' => 'required'
            ],
        ];

        $usersTable = $dataset->createTable('users', ['schema' => ['fields' => $fields1]]);
        $imagesTable = $dataset->createTable('images', ['schema' => ['fields' => $fields2]]);

        if ($usersTable->insertRows([
            [
                'data' => [
                    'user_id' => 'user1',
                    'name' => 'User One'
                ]
            ],
            [
                'data' => [
                    'user_id' => 'user2',
                    'name' => 'User Second'
                ]
            ],
        ])->isSuccessful()) {
            $output->writeln('Users have been inserted');
        }

        if ($imagesTable->insertRows([
            [
                'data' => [
                    'image_id' => 'image1',
                    'user_id' => 'user1',
                    'image_src' => 'path to image 1'
                ]
            ],
            [
                'data' => [
                    'image_id' => 'image2',
                    'user_id' => 'user1',
                    'image_src' => 'path to image 2'
                ]
            ],
            [
                'data' => [
                    'image_id' => 'image3',
                    'user_id' => 'user2',
                    'image_src' => 'path to image 3'
                ]
            ],
            [
                'data' => [
                    'image_id' => 'image4',
                    'user_id' => 'user2',
                    'image_src' => 'path to image 4'
                ]
            ],
        ])->isSuccessful()) {
            $output->writeln('Images have been inserted');
        }

        // CREATE VIEW
        $prefix = sprintf('%s.%s', $this->projectId, $datasetId);
        $query = sprintf('SELECT i.user_id, i.image_id, i.image_src, u.name FROM `%s.images` AS i INNER JOIN `%s.users` AS u ON i.user_id = u.user_id', $prefix, $prefix);

        $view = $dataset->createTable('images_users', ['view' => ['query' => $query, 'useLegacySql' => false]]);

        if ($view->exists()) {
            $output->writeln('View has been created');
        }

        return $dataset;
    }
}
