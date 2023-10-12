<nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
          <a class="navbar-brand" href="#">Logo</a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
              <li class="nav-item">
                <a class="nav-link active" aria-current="page" href="index.php">Strona Główna</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="house.php">Dom</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#">Ustawienia</a>
              </li>
              <li class="nav-item">
              <?php
              
                if (!isset($_SESSION['username'])) {
                  // Użytkownik nie jest zalogowany, przekieruj go na stronę logowania lub gdzie indziej.
                  echo '<a class="nav-link" href="login.php">Konto</a>';
                  
                }
                if (isset($_SESSION['username'])) 
                {
                  echo '
                  <div class="dropdown">
                    <a class="nav-link dropdown-toggle" id="twoje-konto-link" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Twoje konto
                    </a>
                    <div class="dropdown-menu" aria-labelledby="twoje-konto-link">
                        <a class="dropdown-item" href="konto.php">Konto</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="wyloguj.php">Wyloguj</a>
                    </div>
                  </div>
                  ';
                }
              ?>
                
              </li>
            </ul>
          </div>
        </div>
    </nav>