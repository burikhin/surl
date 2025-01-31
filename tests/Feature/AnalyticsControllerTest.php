<?php

it('getUrlAnalytics 404', function () {
    $response = $this->getJson('api/analytics/0');
    $response->assertStatus(404);
});

it('getUrlAnalyticsByToken 404', function () {
    $response = $this->getJson('api/analytics/t/2wngak');
    $response->assertStatus(404);
});

it('getUrlAnalyticsByToken 404 incorrect token', function () {
    $response = $this->getJson('api/analytics/t/0');
    $response->assertStatus(404);
});
