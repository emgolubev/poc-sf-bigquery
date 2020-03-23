# BigQuery usage prototype
> There is a Symfony application with Google BigQuery usage examples

## Table of contents
- [Installation](#installation)
- [Usage](#usage)

## Installation

Clone this repository and install dependencies. 

```console
$ git clone git@github.com:emgolubev/poc-sf-bigquery.git
...
$ composer install
...
```

It also provides Docker support, you can up the containers with `docker-compose` command.

```console
$ docker-compose up -d
```

## Usage

This prototype provides a CLI command `bigquery:test <datasetId>` which:
- creates a BigQuery client;
- check dataset exists, if it does not the command does following steps:
    - create a dataset;
    - create 2 tables (`images` and `users`) and a view `images_users` based on these tables;
    - insert dummy data into tables;
- then it makes a query to the view to get all records;
- and puts results to CSV file `/var/results.csv`

There is an example of usage:

```console
$ bin/console bigquery:test dataset
...
Big Query Testing
============

Table found
images
============

Table found
images_users
============

Table found
users
============

Waiting for job to complete

Results have been saved in /var/results.csv
```

More details about command you can find in [BigQueryTestCommand](src/Command/BigQueryTestCommand.php)

More examples of BigQuery usage you can find in [Google Examples repo](https://github.com/GoogleCloudPlatform/php-docs-samples/tree/master/bigquery/api/src)
