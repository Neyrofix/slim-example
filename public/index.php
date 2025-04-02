<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$app->get('/users', function ($request, $response) {
    $file = file_get_contents(__DIR__ . '/../files/users.json');
    $users = json_decode($file, true);
    $term = $request->getQueryParam('term', '');
    $filteredUsers = array_filter($users, function ($user) use ($term) {
        return str_contains($user['name'], $term);
    });
    $params = ['users' => $filteredUsers, 'term' => $term];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});

$app->get('/users/new', function ($request, $response) {
    return $this->get('renderer')->render($response, 'users/newUser.phtml');
});

$app->post('/users', function ($request, $response) {
    $user = $request->getParsedBodyParam('user');
    $file = file_get_contents(__DIR__ . '/../files/users.json');
    $users = json_decode($file, true);
    $user['id'] = count($users) + 1;
    $users[] = $user;
    $users = json_encode($users);
    file_put_contents(__DIR__ . '/../files/users.json', $users);
    return $response->withRedirect('/users', 302);
});

$app->run();