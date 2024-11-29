<?php
session_start();

// Configuration de la connexion à la base de données
$host = 'localhost';
$dbname = 'gestion_ecole';
$username = 'root';
$password = '12345678@Amine';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Gestion de la connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = md5($_POST['password']); // Mot de passe chiffré (MD5 pour simplifier)

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user'] = $user;
        header('Location: index.php');
        exit();
    } else {
        $error = "Nom d'utilisateur ou mot de passe incorrect.";
    }
}

// Déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Vérifie si l'utilisateur est connecté
$user = $_SESSION['user'] ?? null;

// Redirige vers la page de connexion si non connecté
if (!$user && !isset($_GET['login'])) {
    header('Location: ?login');
    exit();
}

// Ajouter ou modifier un enseignant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_teacher'])) {
    $name = $_POST['teacher_name'];
    $subject = $_POST['subject'];
    $teacherId = $_POST['teacher_id'] ?? null;

    if ($teacherId) {
        $stmt = $pdo->prepare("UPDATE teachers SET name = ?, subject = ? WHERE id = ?");
        $stmt->execute([$name, $subject, $teacherId]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO teachers (name, subject) VALUES (?, ?)");
        $stmt->execute([$name, $subject]);
    }

    header('Location: index.php');
    exit();
}

// Supprimer un enseignant
if (isset($_GET['delete_teacher'])) {
    $id = $_GET['delete_teacher'];

    // Dissocier l'enseignant des classes avant de le supprimer
    $stmt = $pdo->prepare("UPDATE classes SET teacher_id = NULL WHERE teacher_id = ?");
    $stmt->execute([$id]);

    // Supprimer l'enseignant
    $stmt = $pdo->prepare("DELETE FROM teachers WHERE id = ?");
    $stmt->execute([$id]);

    header('Location: index.php');
    exit();
}

// Ajouter ou modifier une classe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_class'])) {
    $name = $_POST['class_name'];
    $teacherId = $_POST['teacher_id'] ?? null;
    $classId = $_POST['class_id'] ?? null;

    if ($classId) {
        $stmt = $pdo->prepare("UPDATE classes SET name = ?, teacher_id = ? WHERE id = ?");
        $stmt->execute([$name, $teacherId, $classId]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO classes (name, teacher_id) VALUES (?, ?)");
        $stmt->execute([$name, $teacherId]);
    }

    header('Location: index.php');
    exit();
}

// Supprimer une classe
if (isset($_GET['delete_class'])) {
    $id = $_GET['delete_class'];
    $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: index.php');
    exit();
}

// Ajouter ou modifier un élève
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_student'])) {
    $name = $_POST['student_name'];
    $age = $_POST['student_age'];
    $classId = $_POST['class_id'] ?? null;
    $studentId = $_POST['student_id'] ?? null;

    if ($studentId) {
        $stmt = $pdo->prepare("UPDATE students SET name = ?, age = ?, class_id = ? WHERE id = ?");
        $stmt->execute([$name, $age, $classId, $studentId]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO students (name, age, class_id) VALUES (?, ?, ?)");
        $stmt->execute([$name, $age, $classId]);
    }

    header('Location: index.php');
    exit();
}

// Supprimer un élève
if (isset($_GET['delete_student'])) {
    $id = $_GET['delete_student'];
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: index.php');
    exit();
}

// Récupération des données
$students = $pdo->query("SELECT students.*, classes.name AS class_name FROM students LEFT JOIN classes ON students.class_id = classes.id")->fetchAll(PDO::FETCH_ASSOC);
$teachers = $pdo->query("SELECT * FROM teachers")->fetchAll(PDO::FETCH_ASSOC);
$classes = $pdo->query("SELECT classes.*, teachers.name AS teacher_name FROM classes LEFT JOIN teachers ON classes.teacher_id = teachers.id")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion d'École</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <?php if (isset($_GET['login'])): ?>
            <!-- Formulaire de connexion -->
            <div class="login-container">
                <form method="POST">
                    <h2>Connexion</h2>
                    <?php if (isset($error)): ?>
                        <p class="error"><?= $error ?></p>
                    <?php endif; ?>
                    <input type="text" name="username" placeholder="Nom d'utilisateur" required>
                    <input type="password" name="password" placeholder="Mot de passe" required>
                    <button type="submit" name="login">Se connecter</button>
                </form>
            </div>
        <?php else: ?>
            <h1>Bienvenue, <?= htmlspecialchars($user['username']) ?>!</h1>
            <p>Rôle : <?= $user['role'] ?></p>
            <a href="?logout" class="logout-btn">Se déconnecter</a>

            <?php if ($user['role'] === 'admin'): ?>
                <!-- Gestion des enseignants -->
                <h2>Enseignants</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Matière</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teachers as $teacher): ?>
                            <tr>
                                <td><?= $teacher['id'] ?></td>
                                <td><?= $teacher['name'] ?></td>
                                <td><?= $teacher['subject'] ?></td>
                                <td>
                                    <a href="?edit_teacher=<?= $teacher['id'] ?>">Modifier</a>
                                    <a href="?delete_teacher=<?= $teacher['id'] ?>" class="delete-btn">Supprimer</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <form method="POST">
                    <input type="hidden" name="teacher_id" value="<?= $_GET['edit_teacher'] ?? '' ?>">
                    <input type="text" name="teacher_name" placeholder="Nom" required>
                    <input type="text" name="subject" placeholder="Matière enseignée" required>
                    <button type="submit" name="save_teacher">Sauvegarder</button>
                </form>

                <!-- Gestion des classes -->
                <h2>Classes</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Enseignant</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes as $class): ?>
                            <tr>
                                <td><?= $class['id'] ?></td>
                                <td><?= $class['name'] ?></td>
                                <td><?= $class['teacher_name'] ?: 'Aucun' ?></td>
                                <td>
                                    <a href="?edit_class=<?= $class['id'] ?>">Modifier</a>
                                    <a href="?delete_class=<?= $class['id'] ?>" class="delete-btn">Supprimer</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <form method="POST">
                    <input type="hidden" name="class_id" value="<?= $_GET['edit_class'] ?? '' ?>">
                    <input type="text" name="class_name" placeholder="Nom de la classe" required>
                    <select name="teacher_id">
                        <option value="">Sans enseignant</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?= $teacher['id'] ?>"><?= $teacher['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="save_class">Sauvegarder</button>
                </form>

                <!-- Gestion des élèves -->
                <h2>Élèves</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Âge</th>
                            <th>Classe</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?= $student['id'] ?></td>
                                <td><?= $student['name'] ?></td>
                                <td><?= $student['age'] ?></td>
                                <td><?= $student['class_name'] ?: 'Non assignée' ?></td>
                                <td>
                                    <a href="?edit_student=<?= $student['id'] ?>">Modifier</a>
                                    <a href="?delete_student=<?= $student['id'] ?>" class="delete-btn">Supprimer</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <form method="POST">
                    <input type="hidden" name="student_id" value="<?= $_GET['edit_student'] ?? '' ?>">
                    <input type="text" name="student_name" placeholder="Nom de l'élève" required>
                    <input type="number" name="student_age" placeholder="Âge" required>
                    <select name="class_id">
                        <option value="">Sans classe</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= $class['id'] ?>"><?= $class['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="save_student">Sauvegarder</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
