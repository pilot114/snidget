<?php

namespace App\Module\Core\HTTP\Controller;

use Snidget\HTTP\Route;
use Snidget\HTTP\Router;
use Snidget\Kernel\MiddlewareManager;

#[Route(prefix: 'admin')]
class Admin
{
    /** @var array<string, string> url => название */
    protected array $links = [
        '/admin'          => 'Dashboard',
        '/admin/routes'   => 'Endpoints',
        '/admin/domain'   => 'Domain',
        '/admin/database' => 'Database',
        '/?SPX_KEY=dev&SPX_UI_URI=/'  => 'Profiler',
    ];

    #[Route(regex: '')]
    public function index(): string
    {
        return $this->template(
            $this->links(),
            '<b>Common counts by other admin pages + quality code info + project and repo info</b>'
        );
    }

    #[Route(regex: 'routes')]
    public function routes(Router $router, MiddlewareManager $mw): string
    {
        // короткие имена: без App\, Module\ и слоя HTTP, вместо \ — :
        $short = str_replace(
            ['App\\', 'Module\\', 'HTTP\Controller\\', 'HTTP\Middleware\\', '\\'],
            ['', '', '', '', ':'],
            ?
        );

        $actions = [];
        foreach ($router->routes() as ['regex' => $regex, 'fqn' => $route]) {
            $actions[] = [
                'URI' => sprintf('<a href="/%s">/%s</a>', $regex, htmlentities($regex)),
                'Controllers' => $short($route),
                'Middlewares' => $mw->match(...explode('::', $route))->getMiddlewares()
                    |> $short
                    |> implode('<br>', ?),
            ];
        }

        return $this->template(
            $this->links(),
            $this->table('All register routes in load order', $actions)
            . $this->table('All register commands in load order', [])
        );
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

    protected function template(string $links, string $content): string
    {
        return "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { display: flex; gap: 2rem; margin: 0; padding: 1rem 2rem; }
        body { font: 14px/1.6 system-ui, sans-serif; color: #1e293b; }
        ul { margin: 0; padding: 0; list-style: none; }
        li { margin: .5rem 0; }
        a { color: #2563eb; text-decoration: none; }
        a:hover { text-decoration: underline; }
        main { flex: 1; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: .5rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <nav>$links</nav>
    <main>$content</main>
</body>
</html>";
    }

    protected function links(): string
    {
        $out = '<ul>';
        foreach ($this->links as $url => $name) {
            $out .= "<li><a href='$url'>$name</a></li>";
        }

        $out .= '</ul>';
        return $out;
    }

    /**
     * @param list<array<string, string>> $data
     */
    protected function table(string $name, array $data): string
    {
        $out = "<h3>$name</h3>";
        $out .= '<table><thead><tr>';
        foreach (array_keys($data[0] ?? []) as $header) {
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
