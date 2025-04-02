<?php

session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

$file = file_get_contents(__DIR__ . '/../files/users.json');
$users = json_decode($file, true);

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$app = AppFactory::createFromContainer($container);


$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app->addErrorMiddleware(true, true, true);

$app->get('/users', function ($request, $response) use ($users) {
    $term = $request->getQueryParam('term', '');
    $filteredUsers = array_filter($users, function ($user) use ($term) {
        return str_contains(strtolower($user['name']), $term);
    });
    $messages = $this->get('flash')->getMessages();
    $params = [
        'users' => $filteredUsers,
        'term' => $term,
        'messages' => $messages
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

$app->get('/users/new', function ($request, $response) use ($users) {
    return $this->get('renderer')->render($response, 'users/newUser.phtml');
})->setName('newUser');

$app->post('/users', function ($request, $response) use ($users) {
    $user = $request->getParsedBodyParam('user');
    $user['id'] = count($users) + 1;
    $users[] = $user;
    $users = json_encode($users);
    file_put_contents(__DIR__ . '/../files/users.json', $users);
    $this->get('flash')->addMessage('success', 'User created successfully');
    return $response->withRedirect('/users', 302);
})->setName('createUser');

$app->get('/users/{id}', function ($request, $response) use ($users) {
    $id = $request->getAttribute('id');
    $user = array_filter($users, function ($user) use ($id) {
        return $user['id'] == $id;
    });
    if (empty($user)) {
        return $response->withRedirect('/users', 404);
    }
    $params = ['user' => $user];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('showUser');

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response, $args) use ($router) {
    $url = $router->urlFor('users');

    return $this->get('renderer')->render($response, 'users/index.phtml', ['url' => $url]);
})->setName('index');

$app->run();
