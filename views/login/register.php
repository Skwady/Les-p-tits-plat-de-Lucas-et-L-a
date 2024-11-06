<?php
$link = 'main';
$title = 'Register';
?>

<div class="background-custom"></div>

<section class="text-center d-flex justify-content-center align-items-center login">
    <div class="container p-4 col-12 col-md-8 col-lg-4 bg-gradiant">
        <h2>Inscription</h2>
        <form method="POST" class="p-3" id="loginForm" action="/login/register">
            <div class="form-group">
                <label for="firstname">nom :</label>
                <input type="text" name="firstname" id="firstname" class="form-control mb-3">
            </div>
            <div class="form-group">
                <label for="name">prenom :</label>
                <input type="text" name="name" id="name" class="form-control mb-3">
            </div>
            <div class="form-group">
                <label for="email">Email :</label>
                <input type="email" name="email" id="email" class="form-control mb-3">
            </div>
            <div class="form-group">
                <label for="password">Mot de passe :</label>
                <input type="password" name="password" id="password" class="form-control mb-3">
            </div>
            <div class="form-group">
                <label for="confirmPassword">Confirmation du mot de passe :</label>
                <input type="Password" name="confirmPassword" id="confirmPassword" class="form-control mb-3">
            </div>
            <div class="formLogin">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit" id="submit" class="btn bg-gradiant2 tw border w-50">Inscription</button> 
            </div>
        </form>
        <div class="my-3" id="error-message" style="color: red;"></div>
    </div>    
</section>