<?php



namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Autor;
use Illuminate\Database\Seeder;

// Seeder com autores e biografias usadas no ambiente local.
class AutorSeeder extends Seeder
{
    // Insere autores padrao para testes e demonstracao.
    public function run()
    {
        // Bloco principal com autores classicos e contemporaneos usados no catalogo inicial.
        Autor::create([
            'nome' => 'José Saramago',
            'foto' => 'images/autores/saramago.jpg',
            'bibliografia' => 'José Saramago foi um escritor português vencedor do Prémio Nobel da Literatura em 1998. É autor de obras marcantes como Ensaio sobre a Cegueira e O Ano da Morte de Ricardo Reis.'
        ]);
        Autor::create([
            'nome' => 'Machado de Assis',
            'foto' => 'images/autores/machado.jpg',
            'bibliografia' => 'Machado de Assis foi um dos maiores escritores da literatura brasileira. Fundador da Academia Brasileira de Letras, destacou-se com obras como Dom Casmurro e Memórias Póstumas de Brás Cubas.'
        ]);
        Autor::create([
            'nome' => 'George Orwell',
            'foto' => 'images/autores/orwell.jpg',
            'bibliografia' => 'George Orwell foi um escritor britânico conhecido pelas suas críticas ao totalitarismo. Entre as suas obras mais famosas estão 1984 e A Revolução dos Bichos.'
        ]);
        Autor::create([
            'nome' => 'Paulo Coelho',
            'foto' => 'images/autores/coelho.jpg',
            'bibliografia' => 'Paulo Coelho é um escritor brasileiro conhecido mundialmente pelo livro O Alquimista. Suas obras abordam espiritualidade, destino e autoconhecimento.'
        ]);
        Autor::create([
            'nome' => 'Clarice Lispector',
            'foto' => 'images/autores/lispector.jpg',
            'bibliografia' => 'Clarice Lispector foi uma escritora brasileira conhecida pela profundidade psicológica das suas obras. Entre seus livros destacam-se A Hora da Estrela e A Paixão Segundo G.H.'
        ]);
        Autor::create([
            'nome' => 'Jorge Amado',
            'foto' => 'images/autores/amado.jpg',
            'bibliografia' => 'Jorge Amado foi um escritor brasileiro conhecido por retratar a cultura e o povo da Bahia. Entre suas obras mais famosas estão Capitães da Areia e Gabriela, Cravo e Canela.'
        ]);

        Autor::create([
            'nome' => 'Carlos Drummond de Andrade',
            'foto' => 'images/autores/drummond.jpg',
            'bibliografia' => 'Carlos Drummond de Andrade foi um poeta brasileiro considerado um dos maiores da literatura do século XX. Sua obra aborda temas existenciais e sociais.'
        ]);
        Autor::create([
            'nome' => 'Lima Barreto',
            'foto' => 'images/autores/limabarreto.jpg',
            'bibliografia' => 'Lima Barreto foi um escritor brasileiro conhecido por criticar as desigualdades sociais e o racismo no Brasil. Sua obra mais famosa é Triste Fim de Policarpo Quaresma.'
        ]);
        Autor::create([
            'nome' => 'Carlos Marques Pereira',
            'foto' => 'images/autores/carlospereira.jpg',
            'bibliografia' => 'Carlos Marques Pereira é um escritor e pesquisador da literatura brasileira que aborda temas sociais e culturais em suas obras.'
        ]);
        Autor::create([
            'nome' => 'Fernando Pessoa',
            'foto' => 'images/autores/pessoa.jpg',
            'bibliografia' => 'Fernando Pessoa foi um dos maiores poetas da língua portuguesa. Criou diversos heterónimos como Álvaro de Campos, Ricardo Reis e Alberto Caeiro.'
        ]);
        Autor::create([
            'nome' => 'J.K. Rowling',
            'foto' => 'images/autores/jkrowling.jpg',
            'bibliografia' => 'J.K. Rowling é uma escritora britânica famosa por criar a série Harry Potter, uma das sagas literárias mais populares da história.'
        ]);

        // Cada registo inclui caminho da foto e texto de bibliografia para exibição no frontend.
    }
}



