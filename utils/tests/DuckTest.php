<?php

use App\Schema\API\UserLogin;
use App\Module\Core\Domain\Duck;
use PHPUnit\Framework\TestCase;
use Snidget\Kernel\Schema\AppPaths;

class DuckTest extends TestCase
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, string[]>
     */
    protected function errors(array $payload): array
    {
        $duck = new Duck(new AppPaths(dirname(__DIR__, 2) . '/App')->getSchemaPaths());
        return iterator_to_array($duck->layAnEgg($payload));
    }

    public function testValidPayloadTwiceInOneProcess(): void
    {
        $this->assertSame([], $this->errors(['login' => 'abcd', 'password' => '12345678']));
        $this->assertSame([], $this->errors(['password' => '12345678', 'login' => 'abcd']));
    }

    public function testNonStringIsRejected(): void
    {
        $errors = $this->errors(['login' => 12345678, 'password' => '12345678']);

        $this->assertSame([UserLogin::class . '::login'], array_keys($errors));
    }

    public function testUnknownFields(): void
    {
        $this->assertArrayHasKey('payload', $this->errors(['foo' => 1]));
    }
}
