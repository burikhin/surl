# Url shortener

This project is providing shortened URLs service via a CSV file uploads. Build with PHP and Laravel.

## Requirements
```
PHP ^8.2
Composer
Docker
```

## How to run

First we need to install all of the packeges with composer. To do that run this command from the project root directory.
```
composer install
```

Now we need to create a local configuration file. Lets copy the example provided.
```
cp .env.example .env
```

Now you can set your db connection params in the .env file

To run the database we will need to run docker with docker-compose.yml configuration file with for postrges image.
```
docker-compose up
```

After this the docker container with database will be running in the background. Make sure to check that your db password and username are matching in .env and docker-compose.yml

If you'll need to restart and recreate the docker db container you can use this command.
```
docker-compose up --force-recreate --build
```

After the database container is ready we can run database migrations.
```
php artisan migrate
```

There are multiple ways of starting server locally. I suggest running it with this command. It is picking up the php.ini config provided in the repository that increses the limits for file uploads. It is usefull for testing with large amount of data in the files. (Othervise depending on your local php configuration some files might not upload)
```
php -c php.ini -S localhost:8000 -t public
```

If you are not testing with large amount of data you can run laravel server with
```
//With composer
composer run dev

//Or with artisan
php artisan serve
```

After the server is up and running we should also start the queue worker. It is going to be processing uploaded files in the background.
```
php artisan queue:work
```

I have created a few tests which are mostly testing that validation rules and exeptions are handeled properly in the controllers and it's a good time to try running it.
```
php artisan test
```

Now when everything is up and running you can import the postman collection provided in the repository into your api client and go do some tests!
```
surl.postman_collection.json
```

## Test data

You can find examples of test .csv files in the '/data' folder, few small ones, one with 40k records and one with 1M. Csv files are expected to have a header row with 'url' column. If it is not present the data won't be mapped properly and parsed. I was thinking that our users would maybe want to extend the amount of information uploaded in the future. For example add category tags associated with urls, etc.

## Application architecture

1. When .csv file is uploaded with (POST /api/surl) and saved to local file storage. The processing job is scheduled and it is going to be picked up by the queue worker later on. This is made to make sure that we can process large amounts of data without relying on running the initial request for a very long time.

2. Queue worker picks up the processing job, opens .csv file and parses its content checking if urls are correct. Valid urls are prepared for saving in batches to make sure the we won't be overloading our database with thousands of requests.

3. To create the short urls we are using unique generated ids of our newly inserted data. Ids are transformed with Base58 encoding to enhance readability and avoid confusion between visually similar characters.
```
Base 58 Encoding: A variation of Base 64 that excludes characters that are not URL-safe (e.g., +, /) and similar-looking characters like 0 (zero), O (capital o), I (capital i), and l (lowercase L) to enhance readability.
```
4. After the file is processed we can go and look up newly created links with (GET /api/surl). The fields we are interested at are 'token' and 'redirect_url'. We can copy 'redirect_url' value and paste it to the browser to test the redirect functionality. This can also be done with postman (GET /r/{token}).

5. When user goes through the 'redirect_url' we are collecting his 'useragent' and 'timestamp' with a middleware to save statistics of the visits. I have chosen middleware for this task so this functionality could be easely tweeked, reused or disabled upon need.

6. To visit usage analitics we have two endpoints. First on is (GET /api/analytics/{id}) to get information by id and the second one (GET /api/analytics/t/{token}) to get it by token.
