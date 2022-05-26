<?php

namespace App\HTTP\Controller;

use Snidget\Attribute\Route;
use Snidget\MiddlewareManager;
use Snidget\Router;

#[Route(prefix: 'admin')]
class Admin
{
    #[Route(regex: '')]
    public function index(): string
    {
        return $this->template($this->links(), '<h3>Common info</h3>');
    }

    #[Route(regex: 'routes')]
    public function routes(Router $router, MiddlewareManager $mw): string
    {
        $data = [];
        foreach ($router->routes() as $regex => $route) {
            $routeMw = $mw->match(...explode('::', $route))->getMiddlewares();
            $routeMw = array_map(fn($x) => implode('::', $x), $routeMw);
            $data[] = [
                'url' => sprintf('<a href="/%s">/%s</a>', $regex, htmlentities($regex)),
                'fqn' => $route,
                'middlewares' => implode('<br>', $routeMw),
            ];
        }
        return $this->template($this->links(), $this->table('All register routes', $data));
    }

    #[Route(regex: 'domain')]
    public function domain(): string
    {
        return $this->template($this->links(), '<h3>Domain</h3>');
    }

    #[Route(regex: 'database')]
    public function database(): string
    {
        return $this->template($this->links(), '<h3>DB entities</h3>');
    }

    protected function template($links, $content): string
    {
        return "<!DOCTYPE html>
<html>
<head>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.css'/>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/milligram/1.4.1/milligram.css'/>
    <style>
        body {
            font-size: 14px;
        }
        td,th {
            padding: 0.5rem;
        }
    </style>
</head>
<body>
    <div class='container'>
        <div class='row'>
            <div class='column column-10'>$links</div>
            <div class='column column-offset-0'>$content</div>
        </div>
    </div>
</body>
</html>";
    }

    protected function links(): string
    {
        $links = [
            '/admin' => 'Dashboard',
            '/admin/routes' => 'Routing',
            '/admin/domain' => 'Domain',
            '/admin/database' => 'Database',
        ];

        $out = '<ul>';
        foreach ($links as $url => $name) {
            $out .= "<li><a href='$url'>$name</a></li>";
        }
        $out .= '</ul>';
        return $out;
    }

    protected function table(string $name, array $data): string
    {
        $out = "<h3>$name</h3>";
        $out .= '<table><thead><tr>';
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