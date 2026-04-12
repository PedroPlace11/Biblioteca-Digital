<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Encripta numero_leitor em users e adiciona sequencial técnico para geração automática.
     */
    public function up(): void
    {
        // Adiciona coluna técnica sequencial para suportar geração/ordenação sem depender do valor encriptado.
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('numero_leitor_seq')->nullable()->unique()->after('numero_leitor');
        });

        // Carrega utilizadores para processar migração de dados em memória.
        $users = DB::table('users')
            ->select('id', 'numero_leitor', 'numero_leitor_seq')
            ->orderBy('id')
            ->get();

        // Guarda maior sequencial encontrado para corrigir valores inválidos sem colisão.
        $maxSeq = 0;

        // Primeira passagem: detecta maior sequencial existente (criptografado ou em texto simples).
        foreach ($users as $user) {
            $valorAtual = (string) ($user->numero_leitor ?? '');

            // Ignora registos sem número de leitor.
            if ($valorAtual === '') {
                continue;
            }

            try {
                // Tenta desencriptar para lidar com base parcialmente migrada.
                $valorAtual = Crypt::decryptString($valorAtual);
            } catch (\Throwable $e) {
                // Valor ainda em texto simples, segue o fluxo normal.
            }

            // Extrai parte numérica do formato L000001.
            $seq = (int) preg_replace('/\D+/', '', $valorAtual);
            // Atualiza maior sequencial conhecido.
            if ($seq > $maxSeq) {
                $maxSeq = $seq;
            }
        }

        // Segunda passagem: normaliza valor, encripta numero_leitor e preenche numero_leitor_seq.
        foreach ($users as $user) {
            $valorAtual = (string) ($user->numero_leitor ?? '');

            // Ignora registos sem número de leitor.
            if ($valorAtual === '') {
                continue;
            }

            try {
                // Se já estiver encriptado, obtém valor plano.
                $valorPlano = Crypt::decryptString($valorAtual);
            } catch (\Throwable $e) {
                // Se não estiver encriptado, usa o valor atual como texto plano.
                $valorPlano = $valorAtual;
            }

            // Extrai sequencial do valor plano.
            $seq = (int) preg_replace('/\D+/', '', $valorPlano);
            if ($seq <= 0) {
                // Para valores inválidos, cria novo sequencial e reescreve no padrão L000001.
                $seq = ++$maxSeq;
                $valorPlano = sprintf('L%06d', $seq);
            }

            // Persiste versão encriptada + sequencial técnico.
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'numero_leitor' => Crypt::encryptString($valorPlano),
                    'numero_leitor_seq' => $seq,
                ]);
        }
    }

    /**
     * Reverte para valor em texto simples e remove o sequencial técnico.
     */
    public function down(): void
    {
        // Carrega utilizadores com numero_leitor para reverter encriptação.
        $users = DB::table('users')
            ->select('id', 'numero_leitor')
            ->whereNotNull('numero_leitor')
            ->orderBy('id')
            ->get();

        // Percorre e restaura numero_leitor para texto simples.
        foreach ($users as $user) {
            $valor = (string) $user->numero_leitor;

            try {
                // Se estiver encriptado, desencripta.
                $valor = Crypt::decryptString($valor);
            } catch (\Throwable $e) {
                // Já está em texto simples.
            }

            // Guarda valor normalizado sem encriptação.
            DB::table('users')
                ->where('id', $user->id)
                ->update(['numero_leitor' => $valor]);
        }

        // Remove índice único e coluna técnica sequencial.
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_numero_leitor_seq_unique');
            $table->dropColumn('numero_leitor_seq');
        });
    }
};



