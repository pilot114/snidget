<?php

namespace Snidget;

use Snidget\Enum\CLIColor;
use Snidget\Enum\CLIStyle;

class CommandLine
{
    protected bool $isPiped;
    protected int $rows;
    protected int $cols;
    protected int $colors;

    public function __construct()
    {
        // https://stackoverflow.com/a/11327451
        $this->isPiped = (fstat(STDOUT)['mode'] & 0170000) === 0010000;
        $this->rows = intval(`tput lines`);
        $this->cols = intval(`tput cols`);
        $this->colors = max(8, intval(`tput colors`));
    }

    /**
     * Вывод данных
     */
    public function out(string $message, bool $appendEndLine = false, $stream = STDOUT): void
    {
        fwrite($stream, $message . ($appendEndLine ? PHP_EOL : ""));
    }

    /**
     * Перемещение курсора
     */
    public function cursor(string $commandName, int $count = 1, int $row = 1, int $column = 1): void
    {
        $commands = [
            'up'    => "\033[{$count}A",
            'down'  => "\033[{$count}B",
            'right' => "\033[{$count}C",
            'left'  => "\033[{$count}D",
            'to'    => "\033[{$row};{$column}f",
            'save'  => "\0337",
            'load'  => "\0338",
            'hide'  => "\033[?25l",
            'view'  => "\033[?25h",
        ];
        if (isset($commands[$commandName])) {
            $this->out($commands[$commandName], false, STDERR);
        }
    }

    /**
     * Очистить часть экрана, относительно курсора
     */
    public function erase(string $commandName): void
    {
        $commands = [
            'screen' => "\033[2J",
            'line'   => "\033[2K",
            'up'     => "\033[1J",
            'down'   => "\033[J",
            'left'   => "\033[1K",
            'right'  => "\033[K",
        ];
        if (isset($commands[$commandName])) {
            $this->out($commands[$commandName], false, STDERR);
        }
    }

    /**
     * Установить цвет и формат
     */
    public function setStyle(string $frontColor, ?string $backColor = null, ?string $style = null): void
    {
        $frontColors = [
            30 => CLIColor::BLACK,
            CLIColor::RED,
            CLIColor::GREEN,
            CLIColor::YELLOW,
            CLIColor::BLUE,
            CLIColor::MAGENTA,
            CLIColor::CYAN,
            CLIColor::WHITE
        ];
        $backColors = [
            40 => CLIColor::BLACK,
            CLIColor::RED,
            CLIColor::GREEN,
            CLIColor::YELLOW,
            CLIColor::BLUE,
            CLIColor::MAGENTA,
            CLIColor::CYAN,
            CLIColor::WHITE
        ];
        $styles = [
            CLIStyle::RESET,
            CLIStyle::BOLD,
            CLIStyle::DIM,
            4 => CLIStyle::UNDER,
            CLIStyle::BLINK,
            7 => CLIStyle::REV,
            CLIStyle::HIDE
        ];

        $attr = [];
        $attr[] = array_search($frontColor, $frontColors);
        $attr[] = array_search($backColor, $backColors);
        $attr[] = array_search($style, $styles);
        $attr = array_filter($attr);

        $attr = implode(';', $attr);
        $this->out("\033[{$attr}m", false, STDERR);
    }

    /**
     * Звоночек
     */
    public function bell(): void
    {
        $this->out("\007", false, STDERR);
    }

    public function progress($bar = false): void
    {
        $this->cursor('save');
        $i = 0;
        while($i <= 100) {
            $this->cursor('load');
            if ($bar) {
                $progressBar = sprintf("[%s]", str_repeat('#', $i) . str_repeat('.', 100 - $i));
                $this->out($progressBar, false, STDERR);
                $this->out("{$i}% complete", true);
                $this->cursor('up');
            } else {
                $this->out("{$i}% complete");
            }
            usleep(200000);
            $i+= 10;
        }
        $this->out(null, true);
    }

    /**
     * Ожидание ввода
     */
    public function wait(): void
    {
        $this->out("you sure? (y/n):", false, STDERR);
        if (strtolower(fread(STDIN, 1)) === 'y') {
            $this->out('yes!');
        } else {
            $this->out('no!!');
        }
    }

    public function handleInput(): void
    {
        $line = fgets(STDIN);
        if ($line) {
            $this->out($line);
        }
    }
}