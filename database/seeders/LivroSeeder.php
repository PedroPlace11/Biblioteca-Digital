<?php



namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Livro;
use Illuminate\Database\Seeder;

// Seeder com catalogo base de livros para desenvolvimento.
class LivroSeeder extends Seeder
{
    // Cria livros e vincula cada registo aos respetivos autores.
    public function run()
    {
        // Catalogo inicial com obras classicas e contemporaneas.
        // Cada livro recebe metadados (ISBN, capa, preco) e associacao com autores via tabela pivot.
        $livro1 = Livro::create([
            'isbn' => '123456',
            'nome' => '1984',
            'editora_id' => 1,
            'bibliografia' => 'Livro distópico clássico de ficção científica',
            'imagem_capa' => 'images/capas/1984.jpg',
            'preco' => 19.90
        ]);
        // Associa livro ao autor com ID 3 (George Orwell).
        $livro1->autores()->attach(3);

        $livro2 = Livro::create([
            'isbn' => '654321',
            'nome' => 'Dom Casmurro',
            'editora_id' => 2,
            'bibliografia' => 'Clássico da literatura brasileira',
            'imagem_capa' => 'images/capas/domcasmurro.jpg',
            'preco' => 15.00
        ]);
        // Associa livro ao autor com ID 2 (Machado de Assis).
        $livro2->autores()->attach(2);

        $livro3 = Livro::create([
            'isbn' => '9789720049579',
            'nome' => 'O Ano da Morte de Ricardo Reis',
            'editora_id' => 1,
            'bibliografia' => 'Romance de José Saramago publicado em 1984.',
            'imagem_capa' => 'images/capas/ricardoreis.jpg',
            'preco' => 22.90
        ]);
        // Associa livro ao autor com ID 1 (Jose Saramago).
        $livro3->autores()->attach(1);

        $livro4 = Livro::create([
            'isbn' => '9788506081749',
            'nome' => 'O Alquimista',
            'editora_id' => 3,
            'bibliografia' => 'Uma fábula sobre seguir seus sonhos',
            'imagem_capa' => 'images/capas/alquimista.jpg',
            'preco' => 28.90
        ]);
        // Associa livro ao autor com ID 4 (Paulo Coelho).
        $livro4->autores()->attach(4);

        $livro5 = Livro::create([
            'isbn' => '9788535917574',
            'nome' => 'A Hora da Estrela',
            'editora_id' => 4,
            'bibliografia' => 'Novela existencial de Clarice Lispector',
            'imagem_capa' => 'images/capas/horadaestrela.jpg',
            'preco' => 16.90
        ]);
        // Associa livro ao autor com ID 5 (Clarice Lispector).
        $livro5->autores()->attach(5);

        $livro6 = Livro::create([
            'isbn' => '9788535908778',
            'nome' => 'Capitães da Areia',
            'editora_id' => 3,
            'bibliografia' => 'Epic de meninos delinqüentes em Salvador',
            'imagem_capa' => 'images/capas/capitaesareia.jpg',
            'preco' => 25.90
        ]);
        // Associa livro ao autor com ID 6 (Jorge Amado).
        $livro6->autores()->attach(6);

        $livro7 = Livro::create([
            'isbn' => '9788535911978',
            'nome' => 'Sentimento do Mundo',
            'editora_id' => 5,
            'bibliografia' => 'Poesia modernista de Drummond',
            'imagem_capa' => 'images/capas/sentimentomundo.jpg',
            'preco' => 18.50
        ]);
        // Associa livro ao autor com ID 7 (Carlos Drummond de Andrade).
        $livro7->autores()->attach(7);

        $livro8 = Livro::create([
            'isbn' => '9788595085625',
            'nome' => 'Triste Fim de Policarpo Quaresma',
            'editora_id' => 6,
            'bibliografia' => 'Sátira social da vida brasileira',
            'imagem_capa' => 'images/capas/policarpoquaresma.jpg',
            'preco' => 17.90
        ]);
        // Associa livro ao autor com ID 8 (Lima Barreto).
        $livro8->autores()->attach(8);

        $livro9 = Livro::create([
            'isbn' => '9788506069968',
            'nome' => 'Ensaio sobre a Cegueira',
            'editora_id' => 1,
            'bibliografia' => 'Alegoria sobre uma epidemia de cegueira',
            'imagem_capa' => 'images/capas/cegueira.jpg',
            'preco' => 24.90
        ]);
        // Associa livro ao autor com ID 1 (Jose Saramago).
        $livro9->autores()->attach(1);

        $livro10 = Livro::create([
            'isbn' => '9788506085319',
            'nome' => 'O Cortiço',
            'editora_id' => 3,
            'bibliografia' => 'Romance naturalista sobre vida no Rio',
            'imagem_capa' => 'images/capas/cortico.jpg',
            'preco' => 20.00
        ]);
        // Associa livro ao autor com ID 9 (Carlos Marques Pereira, conforme seed).
        $livro10->autores()->attach(9);

        $livro11 = Livro::create([
            'isbn' => '9789720052098',
            'nome' => 'Mensagem',
            'editora_id' => 7,
            'bibliografia' => 'Poema épico português do Pessoa',
            'imagem_capa' => 'images/capas/mensagem.jpg',
            'preco' => 19.00
        ]);
        // Associa livro ao autor com ID 10 (Fernando Pessoa).
        $livro11->autores()->attach(10);

        $livro12 = Livro::create([
            'isbn' => '9789728961825',
            'nome' => 'O Primo Basílio',
            'editora_id' => 2,
            'bibliografia' => 'Romance realista português',
            'imagem_capa' => 'images/capas/primobasilio.jpg',
            'preco' => 21.50
        ]);
        // Associa livro ao autor com ID 1 (Jose Saramago).
        $livro12->autores()->attach(1);

        $livro13 = Livro::create([
            'isbn' => '9788506089706',
            'nome' => 'O Mulato',
            'editora_id' => 8,
            'bibliografia' => 'Primeiro romance naturalista brasileiro',
            'imagem_capa' => 'images/capas/mulato.jpg',
            'preco' => 18.90
        ]);
        // Associa livro ao autor com ID 9.
        $livro13->autores()->attach(9);

        $livro14 = Livro::create([
            'isbn' => '9788506078243',
            'nome' => 'Memórias Póstumas de Brás Cubas',
            'editora_id' => 4,
            'bibliografia' => 'Clássico máximo de Machado de Assis',
            'imagem_capa' => 'images/capas/brascubas.jpg',
            'preco' => 26.90
        ]);
        // Associa livro ao autor com ID 2 (Machado de Assis).
        $livro14->autores()->attach(2);

        $livro15 = Livro::create([
            'isbn' => '9788522006214',
            'nome' => 'Vidas Secas',
            'editora_id' => 3,
            'bibliografia' => 'Retrato do sertão nordestino',
            'imagem_capa' => 'images/capas/vidassecas.jpg',
            'preco' => 19.50
        ]);
        // Associa livro ao autor com ID 2.
        $livro15->autores()->attach(2);

        $livro16 = Livro::create([
            'isbn' => '9788535929577',
            'nome' => 'O Corvo',
            'editora_id' => 5,
            'bibliografia' => 'Poema narrativo de horror e melancolia',
            'imagem_capa' => 'images/capas/corvo.jpg',
            'preco' => 17.00
        ]);
        // Associa livro ao autor com ID 3 (George Orwell).
        $livro16->autores()->attach(3);

        $livro17 = Livro::create([
            'isbn' => '9788506090284',
            'nome' => 'A Revolução dos Bichos',
            'editora_id' => 6,
            'bibliografia' => 'Alegoria política sobre revoluções',
            'imagem_capa' => 'images/capas/revolucaobichos.jpg',
            'preco' => 22.00
        ]);
        // Associa livro ao autor com ID 3.
        $livro17->autores()->attach(3);

        $livro18 = Livro::create([
            'isbn' => '9788506092288',
            'nome' => 'Zahir',
            'editora_id' => 7,
            'bibliografia' => 'Exploração de obsessão e identidade',
            'imagem_capa' => 'images/capas/zahir.jpg',
            'preco' => 24.90
        ]);
        // Associa livro ao autor com ID 4.
        $livro18->autores()->attach(4);

        $livro19 = Livro::create([
            'isbn' => '9788532525437',
            'nome' => 'A Paixão Segundo G.H.',
            'editora_id' => 8,
            'bibliografia' => 'Experiência mística e existencial',
            'imagem_capa' => 'images/capas/paixaoghn.jpg',
            'preco' => 23.00
        ]);
        // Associa livro ao autor com ID 5.
        $livro19->autores()->attach(5);

        $livro20 = Livro::create([
            'isbn' => '9788580443776',
            'nome' => 'Quincas Borba',
            'editora_id' => 1,
            'bibliografia' => 'Questões sobre natureza humana e filosofia',
            'imagem_capa' => 'images/capas/quincasborba.jpg',
            'preco' => 25.50
        ]);
        // Associa livro ao autor com ID 2.
        $livro20->autores()->attach(2);

        // Bloco especial: colecao Harry Potter com autora unica (ID 11).
        $livro21 = Livro::create([
            'isbn' => '9788532530787',
            'nome' => 'Harry Potter e a Pedra Filosofal',
            'editora_id' => 4,
            'bibliografia' => 'A jornada começa quando Harry descoberta que é um bruxo e é convidado a frequentar a escola de bruxaria de Hogwarts.',
            'imagem_capa' => 'images/capas/harrypotter1.jpg',
            'preco' => 34.90
        ]);
        // Associa livro ao autor com ID 11 (J.K. Rowling).
        $livro21->autores()->attach(11);

        $livro22 = Livro::create([
            'isbn' => '9788532511663',
            'nome' => 'Harry Potter e a Camara Secreta',
            'editora_id' => 4,
            'bibliografia' => 'No segundo ano em Hogwarts, Harry investiga uma camara secreta e enfrenta novos perigos.',
            'imagem_capa' => 'images/capas/harrypotter2.jpg',
            'preco' => 36.90
        ]);
        // Associa livro ao autor com ID 11.
        $livro22->autores()->attach(11);

        $livro23 = Livro::create([
            'isbn' => '9788532512066',
            'nome' => 'Harry Potter e o Prisioneiro de Azkaban',
            'editora_id' => 4,
            'bibliografia' => 'Harry descobre segredos do passado da sua familia enquanto um prisioneiro perigoso foge de Azkaban.',
            'imagem_capa' => 'images/capas/harrypotter3.jpg',
            'preco' => 38.90
        ]);
        // Associa livro ao autor com ID 11.
        $livro23->autores()->attach(11);

        $livro24 = Livro::create([
            'isbn' => '9788532512523',
            'nome' => 'Harry Potter e o Calice de Fogo',
            'editora_id' => 4,
            'bibliografia' => 'Harry e selecionado para o Torneio Tribruxo e enfrenta provas perigosas que anunciam o retorno de Voldemort.',
            'imagem_capa' => 'images/capas/harrypotter4.jpg',
            'preco' => 41.90
        ]);
        // Associa livro ao autor com ID 11.
        $livro24->autores()->attach(11);

        $livro25 = Livro::create([
            'isbn' => '9788532516224',
            'nome' => 'Harry Potter e a Ordem da Fenix',
            'editora_id' => 4,
            'bibliografia' => 'Com o Ministerio negando a verdade, Harry cria um grupo de defesa para preparar os alunos contra as artes das trevas.',
            'imagem_capa' => 'images/capas/harrypotter5.jpg',
            'preco' => 43.90
        ]);
        // Associa livro ao autor com ID 11.
        $livro25->autores()->attach(11);

        $livro26 = Livro::create([
            'isbn' => '9788532519478',
            'nome' => 'Harry Potter e o Enigma do Principe',
            'editora_id' => 4,
            'bibliografia' => 'Harry aprende sobre o passado de Voldemort e descobre pistas cruciais para a batalha final.',
            'imagem_capa' => 'images/capas/harrypotter6.jpg',
            'preco' => 44.90
        ]);
        // Associa livro ao autor com ID 11.
        $livro26->autores()->attach(11);

        $livro27 = Livro::create([
            'isbn' => '9788532520610',
            'nome' => 'Harry Potter e as Reliquias da Morte',
            'editora_id' => 4,
            'bibliografia' => 'No desfecho da saga, Harry, Ron e Hermione buscam as horcruxes para derrotar Voldemort de uma vez por todas.',
            'imagem_capa' => 'images/capas/harrypotter7.jpg',
            'preco' => 46.90
        ]);
        // Associa livro ao autor com ID 11.
        $livro27->autores()->attach(11);
    }
}




