<?php

namespace App\HTTP\Controller;

use Snidget\Attribute\Route;
use Snidget\MiddlewareManager;
use Snidget\Router;

#[Route(prefix: 'admin')]
class Admin
{
    #[Route(regex: '')]
    public function index(Router $router, MiddlewareManager $mw): string
    {
        $data = [];
        foreach ($router->routes() as $regex => $route) {
            $routeMw = $mw->match(...explode('::', $route))->getMiddlewares();
            $routeMw = array_map(fn($x) => implode('::', $x), $routeMw);
            $data[] = [
                // TODO: https://github.com/sanduhrs/phpstorm-url-handler
                'fqn' => sprintf('<a href="phpstorm://open?url=file://%s">%s</a>', $route, $route),
                'url' => sprintf('<a href="/%s">/%s</a>', $regex, htmlentities($regex)),
                'middlewares' => implode('<br>', $routeMw)
            ];
        }
        return $this->template([
            'table' => $this->table('All routes', $data)
        ]);
    }

    protected function template(array $items): string
    {
        return "<!DOCTYPE html>
<html>
<head>
<style>
    .styled-table {
        border-collapse: collapse;
        margin: 25px 0;
        font-size: 0.9em;
        font-family: sans-serif;
        min-width: 400px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
    }
    .styled-table thead tr {
        background-color: #009879;
        color: #ffffff;
        text-align: left;
    }
    .styled-table caption {
        background-color: #009879;
        color: #ffffff;
        text-align: center;
        padding: 12px 15px;
        font-size: 1.5em;
        font-weight: bold;
    }
    .styled-table th,
    .styled-table td {
        padding: 12px 15px;
    }
    .styled-table tbody tr {
        border-bottom: 1px solid #dddddd;
    }
    
    .styled-table tbody tr:nth-of-type(even) {
        background-color: #f3f3f3;
    }
    
    .styled-table tbody tr:last-of-type {
        border-bottom: 2px solid #009879;
    }
</style>
</head>
<body>
    {$items['table']}
</body>
</html>";
    }

    protected function table(string $name, array $data): string
    {
        $out = "<table class='styled-table'><caption>$name</caption>";
        $out .= '<thead><tr>';
        foreach (array_keys($data[0]) as $header) {
            $out .= "<th>$header</th>";
        }
        $out .= '</tr></thead><tbody>';
        foreach ($data as $row) {
            $out .= '<tr>';
            foreach ($row as $value) {
                $out .= "<td>$value</td>";
            }
            $out .= '</tr>';
        }
        $out .= '</tbody></table>';
        return $out;
    }
}