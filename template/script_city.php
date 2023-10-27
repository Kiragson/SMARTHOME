<script>
        document.getElementById("postalCode").addEventListener("change", function() {
            var selectedPostalCode = this.value;
            var citySelect = document.getElementById("city");
            
            // Tu powinieneś napisać kod, który na podstawie kodu pocztowego pobierze dostępne miasta i wypełni nimi pole wyboru miasta.
            // Możesz użyć AJAX do pobierania tych danych z zewnętrznego źródła, np. bazy danych.
            
            // Poniżej znajdziesz przykład, który ręcznie dodaje kilka miast w zależności od kodu pocztowego.
            citySelect.innerHTML = ""; // Wyczyść pole wyboru miasta
            
            switch (selectedPostalCode) {
                case "00-001":
                    citySelect.options.add(new Option("Warszawa", "Warszawa"));
                    break;
                case "30-001":
                    citySelect.options.add(new Option("Kraków", "Kraków"));
                    break;
                case "26-600":
                    citySelect.options.add(new Option("Radom", "Radom"));
                    break;    
                // Dodaj więcej przypadków dla innych kodów pocztowych
                default:
                    citySelect.options.add(new Option("Inne", "Inne"));
            }
        });
</script>