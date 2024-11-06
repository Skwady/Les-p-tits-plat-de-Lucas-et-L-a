<?php

namespace App\controllers;

use App\models\LoginModel;
use GuzzleHttp\Psr7\Request;
use PHPMailer\PHPMailer\PHPMailer;

class LoginController extends Controller
{
    /**
     * Displays the login form.
     *
     * Renders the login view where users can enter their email and password
     * to authenticate.
     *
     * @return void Outputs the rendered login view.
     */
    public function index()
    {
        // Render the login view
        $this->render('login/index');
    }

    /**
     * Handles the login process.
     *
     * Validates the submitted email and password. If valid, it retrieves the user
     * from the database and verifies the password. Upon successful verification,
     * user data is stored in the session, allowing access to restricted areas.
     *
     * @return void Redirects to the home page upon successful login or exits with an error message on failure.
     */
    public function conect()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(["status" => "error", "message" => "Méthode de requête non autorisée."]);
            exit();
        }

        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $password = trim($_POST['password']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Email invalide."]);
            exit();
        }

        $LoginModel = new LoginModel();
        $user = $LoginModel->search($email);

        // Vérifier si l'utilisateur est confirmé
        if ($user && !$user->is_confirmed) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Veuillez confirmer votre adresse email avant de vous connecter."]);
            exit();
        }

        if ($user && password_verify($password, $user->password)) {
            $_SESSION['id'] = $user->id;
            $_SESSION['name'] = $user->name;
            $_SESSION['firstname'] = $user->firstname;
            $_SESSION['email'] = $user->email;
            $_SESSION['role'] = $user->id_role;

            http_response_code(200);
            echo json_encode(["status" => "success", "redirect" => "/"]);
        } else {
            $message = $user ? "Mot de passe incorrect." : "Utilisateur non trouvé.";
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => $message]);
        }
        exit();
    }

    public function register()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                header('Content-Type: application/json');
                $name = $_POST['name'];
                $firstname = $_POST['firstname'];
                $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                $password = $_POST['password'];
                $confirmPassword = $_POST['confirmPassword'];
                $id_role = 2;

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    http_response_code(400);
                    echo json_encode(["status" => "error", "message" => "Email invalide."]);
                    exit();
                }

                if ($password !== $confirmPassword) {
                    http_response_code(400);
                    echo json_encode(["status" => "error", "message" => "Les mots de passe ne correspondent pas."]);
                    exit();
                } else {
                    $password = password_hash($password, PASSWORD_DEFAULT);
                }

                // Génération du token de confirmation
                $token = bin2hex(random_bytes(32));

                // Hydrater et enregistrer l'utilisateur avec le token
                $user = (new LoginModel())->hydrate([
                    'name' => $name,
                    'firstname' => $firstname,
                    'email' => $email,
                    'password' => $password,
                    'confirmed_token' => $token,
                    'is_confirmed' => 0,
                    'id_role' => $id_role
                ]);
                if ($user->create()) {
                    // Envoi de l'email de confirmation
                    $this->sendConfirmationEmail($email, $token);

                    http_response_code(200);
                    echo json_encode(["status" => "success", "message" => "Un email de confirmation a été envoyé."]);
                    exit();
                } else {
                    http_response_code(500);
                    echo json_encode(["status" => "error", "message" => "Erreur lors de la création de l'utilisateur."]);
                    exit();
                }
            }
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "l'email existe déjà"]);
            exit();
        }
        $this->render('login/register');
    }

    public function sendConfirmationEmail($email, $token)
    {
        $mail = new PHPMailer(true);
        try {
            // Configuration SMTP
            $mail->isSMTP();
            $mail->Host       = $_ENV['MAIL_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USERNAME'];
            $mail->Password   = $_ENV['MAIL_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $_ENV['MAIL_PORT'];
            $mail->CharSet    = 'UTF-8';

            // Destinataire et expéditeur
            $mail->setFrom($_ENV['MAIL_USERNAME'], 'Lucas et Léa');
            $mail->addAddress($email);

            // Contenu de l'email
            $mail->isHTML(true);
            $mail->Subject = 'Confirmation de votre inscription';
            $confirmationLink = "http://localhost/login/confirm/" . $token;
            $mail->Body = "Bonjour,<br><br>Merci pour votre inscription. 
                       Veuillez cliquer sur le lien ci-dessous pour confirmer votre adresse email :<br>
                       <a href='$confirmationLink'>Confirmer mon email</a>";

            $mail->send();
        } catch (\Exception $e) {
            error_log("Erreur lors de l'envoi de l'email de confirmation : {$mail->ErrorInfo}");
        }
    }

    public function confirm($token = null)
    {
        if ($token) {
            $LoginModel = new LoginModel();
            $user = $LoginModel->findByToken($token);

            if ($user) {
                $LoginModel->confirmUser($user->id);
                echo "Votre compte a été confirmé avec succès !";
            } else {
                echo "Lien de confirmation invalide ou expiré.";
            }
        } else {
            echo "Token manquant.";
        }
    }
}