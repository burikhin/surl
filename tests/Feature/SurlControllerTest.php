<?php

use Illuminate\Http\UploadedFile;

it('createUrlsFromFile 200', function () {
    $file = UploadedFile::fake()->create('file.csv');
    $response = $this->postJson('/api/surl', ['urls' => $file]);
    $response->assertStatus(200);
});

it('createUrlsFromFile 422', function () {
    $response = $this->postJson('/api/surl', ['urls' => null]);
    $response->assertStatus(422);
});

it('getUrlList 200', function () {
    $response = $this->getJson('/api/surl');
    $response->assertStatus(200);
});

it('getUrl 404', function () {
    $response = $this->getJson('/api/surl/0');
    $response->assertStatus(404);
});

it('deleteUrl 200', function () {
    $response = $this->deleteJson('/api/surl/0');
    $response->assertStatus(200);
});
