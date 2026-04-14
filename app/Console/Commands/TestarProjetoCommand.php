<?php

namespace App\Console\Commands;

use App\Models\LogSistema;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

class TestarProjetoCommand extends Command
{
    protected $signature = 'projeto:testar
        {--filtro= : Filtro para nomes de testes Pest}
        {--suite= : Suite de testes (Feature ou Unit)}
        {--paralelo : Executa testes em paralelo}
        {--cobertura : Gera cobertura de testes}
        {--dry-run : Mostra o comando Pest sem executar}';

    protected $description = 'Executa os testes automáticos do projeto com Pest';

    public function handle(): int
    {
        $suite = $this->option('suite');

        if ($suite && ! in_array($suite, ['Feature', 'Unit'], true)) {
            $this->error('Suite inválida. Use apenas Feature ou Unit.');

            return self::FAILURE;
        }

        $pestArgs = [PHP_BINARY, 'artisan', 'test'];

        if ($suite) {
            $pestArgs[] = '--testsuite=' . $suite;
        }

        if ($this->option('filtro')) {
            $pestArgs[] = '--filter=' . $this->option('filtro');
        }

        if ($this->option('paralelo')) {
            $pestArgs[] = '--parallel';
        }

        if ($this->option('cobertura')) {
            $pestArgs[] = '--coverage';
        }

        if ($this->option('dry-run')) {
            $comandoVisual = 'php artisan test';
            $flagsVisuais = array_slice($pestArgs, 3);
            if (! empty($flagsVisuais)) {
                $comandoVisual .= ' ' . implode(' ', $flagsVisuais);
            }

            $this->line('Comando Pest: ' . $comandoVisual);

            return self::SUCCESS;
        }

        $this->info('A executar testes automáticos com Pest...');

        $contexto = $this->montarContextoExecucao();
        $this->registarLogTeste('Execucao de testes iniciada. ' . $contexto);

        $status = self::FAILURE;

        try {
            $processo = new Process($pestArgs, base_path());
            $processo->setTimeout(null);
            $processo->run(function (string $type, string $output): void {
                $this->output->write($output);
            });

            $status = $processo->isSuccessful() ? self::SUCCESS : self::FAILURE;

            return $status;
        } finally {
            $resultado = $status === self::SUCCESS ? 'sucesso' : 'falha';
            $this->registarLogTeste('Execucao de testes finalizada com ' . $resultado . '. ' . $contexto);
        }
    }

    private function montarContextoExecucao(): string
    {
        $partes = [];

        if ($this->option('suite')) {
            $partes[] = 'Suite: ' . (string) $this->option('suite');
        }

        if ($this->option('filtro')) {
            $partes[] = 'Filtro: ' . (string) $this->option('filtro');
        }

        if ($this->option('paralelo')) {
            $partes[] = 'Execucao paralela';
        }

        if ($this->option('cobertura')) {
            $partes[] = 'Com cobertura';
        }

        return empty($partes) ? 'Sem filtros adicionais.' : implode(' | ', $partes);
    }

    private function registarLogTeste(string $alteracao): void
    {
        if (! Schema::hasTable('logs')) {
            return;
        }

        LogSistema::query()->create([
            'ocorrido_em' => now(),
            'user_id' => null,
            'user_nome' => 'Sistema',
            'modulo' => 'Testes',
            'objeto_id' => null,
            'alteracao' => $alteracao,
            'ip' => 'CLI',
            'browser' => 'Artisan CLI',
            'metodo' => 'ARTISAN',
            'url' => 'projeto:testar',
            'route_name' => 'artisan.projeto.testar',
        ]);
    }
}
