<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\BigQueryService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BigQueryTestCommand extends Command
{
    protected static $defaultName = 'bigquery:test';

    /** @var BigQueryService */
    private $bigQueryService;

    public function __construct(BigQueryService $bigQueryService, string $name = null)
    {
        parent::__construct($name);

        $this->bigQueryService = $bigQueryService;
    }

    protected function configure()
    {
        // ...
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            'Big Query Testing',
            '============',
            '',
        ]);

        $datasetId = 'datasetExample2';
        $table1Id = 'users';
        $table2Id = 'images';

        $client = $this->bigQueryService->getClient();

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

        $usersTable = $dataset->createTable($table1Id, ['schema' => ['fields' => $fields1]]);
        $imagesTable = $dataset->createTable($table2Id, ['schema' => ['fields' => $fields2]]);

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

        // clean
        $usersTable->delete();
        $imagesTable->delete();
        $dataset->delete();

        $output->writeln('Tables and dataset have been deleted');

        return 0;
    }
}
