# Url shortener

This project provides a shortened URL service via CSV file uploads. Built with PHP and Laravel.


## Requirements (tested with)

```
PHP 8.4
Composer 2.8
Docker 4.37
```


## How to run

First we need to install all of the packages with composer. To do that run this command from the project root directory.

```
composer install
```

Now we need to create a local configuration file. Lets copy the example provided.

```
cp .env.example .env
```

Now you can set your db connection params in the **.env** file

To run the database we will need to run docker service with docker-compose.yml configuration file for PostgreSQL image.

```
docker-compose up
```

Now the docker container with a database will be running in the background. Make sure to check that your db password and username are matching in **.env** and **docker-compose.yml**

If you need to restart and recreate the docker db container, use this command.

```
docker-compose up --force-recreate --build
```

After the database container is ready, we can run database migrations.

```
php artisan migrate
```

Before running the server, we need to generate app keys

```
php artisan key:generate
```
```
php artisan config:cache
```

There are multiple ways of starting a server locally and I suggest running it with the following command. It is picking up the php.ini config in the root directory (this file increases the limits for file uploads). It is useful for testing files that contain large amounts of data (otherwise, depending on your local php configuration, some files might not upload).

```
php -c php.ini -S localhost:8000 -t public
```

If you are not testing with a large amount of data, you can run the Laravel server with either Composer or Artisan: 

Composer:
```
composer run dev
```

Artisan:
```
php artisan serve
```

After the server is up and running, we should also start the import queue worker. It will process uploaded files in the background.

```
php artisan queue:work
```

Import queue worker is also dispatching batch processing jobs into a separate queue called **batch**. To scale the processing of batches, you can assign multiple workers for **batch** queue with the following command:

```
php artisan queue:work --queue=batch
```

I have created a few tests that ensure validation rules and exceptions are handled properly in the controllers.

```
php artisan test
```

Now when everything is up and running, you can import the Postman collection provided in the **surl.postman_collection.json** file into your api client and go run some tests!



## Test Data

You can find examples of test **.csv** files in the **/data** folder. CSV files are expected to have a header row with one colum - **url**. If it is not present, the data won't be mapped/parsed properly. The header row is important to have if users want to add additonal columns (i.e. category tags) for future uploads.

### Bonus

I have included a golang script which can be used for generating csv files. You'll need golang installed on your machine (v 1.23).

From the root directory, run this command:

```
cd cgen
```

To get feature flags avaliable, run this command:

```
go run cgen.go -h
```

Example Usage

```
go run cgen.go -f=file10k.csv -l=10000 -u="https://duckduckgo.com/?t=h_&q="
```


## Application Architecture

1. When a **.csv** file is uploaded by hitting the **POST /api/surl** endpoint, it is saved to local file storage. The processing job is scheduled and it is going to be picked up by the queue worker later on. Import queue worker is dispatching batch processing jobs into a separate **batch** queue. Processing of batches can be scaled up or down with running multiple workers for **batch** queue. This ensures that we can process large amounts of data without relying on solely running the initial request (which can take a long time).

2. Queue worker picks up the processing job, opens a **.csv** file, parses its content, and checks if the urls are correct. Valid urls are prepared for saving in batches to make sure that we won't be overloading our database with thousands of requests.

3. To create short urls, we are using unique generated ids of our newly inserted data. Ids are transformed with **Base58** encoding to enhance readability and avoid confusion among visually similar characters. 

    >***Base 58 Encoding: A variation of Base 64 that excludes characters that are not URL-safe (e.g., +, /) and similar-looking characters like 0 (zero), O (capital o), I (capital i), and l (lowercase L) to enhance readability.***

4. After the file is processed, we can go and look up newly created links with the following endpoint - **GET /api/surl**. The fields we are interested in are **token** and **redirect_url**. We can copy the **redirect_url** value and paste it to the browser to test the redirect functionality. This can also be done with Postman using the following endpoint - **GET /r/{token}**.

5. When user goes through the **redirect_url**, we are collecting their **ip**, **useragent** and **timestamp** with middleware to save visit statistics. Middleware ensures that functionality can be easely tweaked, reused, or disabled upon need.

6. To visit usage analytics, we have two endpoints: **GET /api/analytics/{id}** to get information by id, **GET /api/analytics/t/{token}** to get it by token.


## Bonus #2

I have added an experimental branch **exp** with a program I have written in golang as an alternative to the Laravel workers included in the project. It is multithreaded and gives significant performance boost compared to running multiple queue workers (php with 5 workers inserts 1 million records in ~30 seconds, while golang and goroutines can import the same amount of data in ~10 seconds). 

The idea is to run the console golang program from the dispatched laravel job, passing in the file to be processed. You may switch to the **exp** branch and compile go executable.

From the root directory run:

```
cd gojob
```
```
go build import.go
```

After that when you are hitting **POST /api/surl** request, include **experimental = 1** in the form-data. The request will dispatch the job and run the golang executable to process the import.
