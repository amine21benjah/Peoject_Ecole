<?php
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    private $pdo;

    protected function setUp(): void
    {
        $host = 'localhost';
        $dbname = 'gestion_ecole'; 
        $username = 'root';
        $password = '12345678@Amine';

        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->fail("Erreur de connexion : " . $e->getMessage());
        }
    }

    public function testDatabaseConnection(): void
    {
        $this->assertNotNull($this->pdo, 'La connexion PDO est nulle.');
    }

    public function testUserLogin(): void
    {
        // Préparer une entrée simulée
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['username'] = 'testuser';
        $_POST['password'] = 'testpass';

        // Insérer un utilisateur dans la base de test
        $this->pdo->exec("INSERT INTO users (username, password) VALUES ('testuser', 'testpass')");

        // Inclure le fichier index.php pour exécuter le code
        ob_start();
        include 'index.php';
        $output = ob_get_clean();

        // Vérifier si la session a été créée
        $this->assertArrayHasKey('user_id', $_SESSION, 'Lutilisateur nest pas connecté.');

        // Nettoyer la base de données
        $this->pdo->exec("DELETE FROM users WHERE username = 'testuser'");
    }

    protected function tearDown(): void
    {
        $this->pdo = null; // Fermer la connexion
    }
}
