<?php

namespace App\Controller;

use Wshell\Snidget\Attribute\Route;

class Post
{
    #[Route('')]
    public function index()
    {
        return 'main page';
    }

    #[Route('post')]
    public function list()
    {
        return 'Post::list';
    }

    #[Route('post/(?<id>\d+)')]
    public function get(int $id)
    {
        return 'Post::get #' . $id;
    }

    #[Route('.*')]
    public function notFound()
    {
        http_response_code(404);
        return '404 Not Found';
    }
}