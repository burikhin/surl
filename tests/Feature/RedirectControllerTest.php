<?php

it('redirect 404', function () {
    $response = $this->getJson('r/0');
    $response->assertStatus(404);
});
