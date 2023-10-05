<?php

declare(strict_types=1);


it('can assert responses', function () {
    $this->get('/')
        ->assertOk()
        ->assertBodyContains('Hello');

    $this->get('/users')
        ->assertNotFound();

    $this->get(path: '/', headers: ['Accept' => 'text/html'])
        ->assertNotAcceptable();

    $this->post('/users', ['name' => 'John Doe'])
        ->assertNotFound();

    $this->put('/users/1')
        ->assertNotFound();

    $this->patch('/users/1')
        ->assertNotFound();

    $this->delete('/users/1')
        ->assertNotFound();
});
