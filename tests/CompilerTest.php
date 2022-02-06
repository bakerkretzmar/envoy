<?php

namespace Laravel\Envoy\Tests;

use Laravel\Envoy\Compiler;
use PHPUnit\Framework\TestCase;

class CompilerTest extends TestCase
{
    public function test_it_compiles_finished_statement()
    {
        $str = <<<'EOL'
@finished
    echo 'shutdown';
@endfinished
EOL;
        $compiler = new Compiler();
        $result = $compiler->compile($str);

        $this->assertSame(1, preg_match('/\$__container->finished\(.*?\}\);/s', $result, $matches));
    }

    public function test_compile_servers_statement()
    {
        $str = <<<'EOL'
@servers(['local' => '127.0.0.1', 'remote' => '1.1.1.1'])
EOL;
        $compiler = new Compiler();
        $result = $compiler->compile($str);

        $this->assertStringContainsString(
            "\$__container->servers(['local' => '127.0.0.1', 'remote' => '1.1.1.1']);",
            $result
        );
    }

    public function test_compile_servers_statement_with_line_breaks()
    {
        $str = <<<'EOL'
@servers([
    'local' => '127.0.0.1',
    'remote' => '1.1.1.1',
])
EOL;
        $compiler = new Compiler();
        $result = $compiler->compile($str);

        $this->assertStringContainsString(
            "\$__container->servers([\n    'local' => '127.0.0.1',\n    'remote' => '1.1.1.1',\n]);",
            $result
        );
    }

    public function serversStatementsProvider(): array
    {
        return [
            ["@servers(['loca))' => '127.0.0.1'])", "['loca))' => '127.0.0.1']"],
            [<<<'EOL'
@servers(['local' => '127.0.0.1', 'staging' => 'example.com', 'production' => 'shortcut'])
@setup
    $releaseName = date('Y-m-d_H-i-s');
@endsetup
EOL, "['local' => '127.0.0.1', 'staging' => 'example.com', 'production' => 'shortcut']"
            ],
            [<<<'EOL'
@servers([
    'lo(cal' => '127.)0.0.1',
    'stag()ing' => 'exam())ple.com']

  )
EOL, <<<'EOL'
[
    'lo(cal' => '127.)0.0.1',
    'stag()ing' => 'exam())ple.com']

  
EOL,
            ],
            ["@servers(['local' => (\$_ENV['TEST'] == true) ? 'test' : '127.0.0.1'])", "['local' => (\$_ENV['TEST'] == true) ? 'test' : '127.0.0.1']"],
        ];
    }

    /**
     * @dataProvider serversStatementsProvider
     */
    public function test_compile_servers_statement_in_context(string $input, string $compiled)
    {
        $compiler = new Compiler();
        $result = $compiler->compile($input);

        $this->assertStringContainsString("\$__container->servers({$compiled}); ?>", $result);
    }

    public function test_compile_before_statement()
    {
        $str = <<<'EOL'
@before
    echo "Running {{ $task }} task.";
@endbefore
EOL;
        $compiler = new Compiler();
        $result = $compiler->compile($str);

        $this->assertSame(1, preg_match('/\$__container->before\(.*?\}\);/s', $result, $matches));
    }
}
