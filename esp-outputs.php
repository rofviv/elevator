<!--
  Rui Santos
  Complete project details at https://RandomNerdTutorials.com/control-esp32-esp8266-gpios-from-anywhere/

  Permission is hereby granted, free of charge, to any person obtaining a copy
  of this software and associated documentation files.

  The above copyright notice and this permission notice shall be included in all
  copies or substantial portions of the Software.
-->
<?php
    session_start();
    $password_correcta = 'Sistemas2022.';
    if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
        if (isset($_POST['password'])) {
            if ($_POST['password'] === $password_correcta) {
                $_SESSION['autenticado'] = true;
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error = 'Contraseña incorrecta';
            }
        }
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Acceso protegido</title>
            <style>
                body { font-family: Arial; background: #f2f2f2; }
                .login-box { background: #fff; padding: 30px; margin: 100px auto; width: 300px; border-radius: 8px; box-shadow: 0 0 10px #ccc; }
                input[type=password], input[type=submit] { width: 100%; padding: 10px; margin: 10px 0; }
                .error { color: red; }
            </style>
        </head>
        <body>
            <div class="login-box">
                <h2>Acceso protegido</h2>
                <?php if (isset($error)) echo '<p class="error">'.$error.'</p>'; ?>
                <form method="post">
                    <input type="password" name="password" placeholder="Contraseña" autofocus required>
                    <input type="submit" value="Entrar">
                </form>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
?>
<?php
    include_once('esp-database.php');

    $result = getAllOutputs();
    $html_buttons = null;
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if ($row["state"] == "1"){
                $button_checked = "checked";
            }
            else {
                $button_checked = "";
            }
            $html_buttons .= '<h3>' . $row["name"] . ' - Board '. $row["board"] . ' - GPIO ' . $row["gpio"] . ' (<i><a onclick="deleteOutput(this)" href="javascript:void(0);" id="' . $row["id"] . '">Delete</a></i>)</h3><label class="switch"><input type="checkbox" onchange="updateOutput(this)" id="' . $row["id"] . '" ' . $button_checked . '><span class="slider"></span></label>';
        }
    }

    $result2 = getAllBoards();
    $html_boards = null;
    if ($result2) {
        $html_boards .= '<h3>Boards</h3>';
        while ($row = $result2->fetch_assoc()) {
            $row_reading_time = $row["last_request"];
            // Uncomment to set timezone to - 1 hour (you can change 1 to any number)
            //$row_reading_time = date("Y-m-d H:i:s", strtotime("$row_reading_time - 1 hours"));

            // Uncomment to set timezone to + 4 hours (you can change 4 to any number)
            //$row_reading_time = date("Y-m-d H:i:s", strtotime("$row_reading_time + 7 hours"));
            $html_boards .= '<p><strong>Board ' . $row["board"] . '</strong> - Last Request Time: '. $row_reading_time . '</p>';
        }
    }
?>

<!DOCTYPE HTML>
<html>
    <head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">

        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" type="text/css" href="esp-style.css">
        <title>ESP Output Control</title>
    </head>
<body>
    <h2>ESP Output Control</h2>
    <div id="status-indicator" style="background: #e8f5e8; padding: 10px; margin: 10px 0; border-radius: 5px; display: none;">
        <strong>Estado:</strong> <span id="status-text">Actualizando...</span>
        <span id="last-update"></span>
    </div>
    <?php echo $html_buttons; ?>
    <br><br>
    <?php echo $html_boards; ?>
    <br><br>
    <div><form onsubmit="return createOutput();">
        <h3>Create New Output</h3>
        <label for="outputName">Name</label>
        <input type="text" name="name" id="outputName"><br>
        <label for="outputBoard">Board ID</label>
        <input type="number" name="board" min="0" id="outputBoard">
        <label for="outputGpio">GPIO Number</label>
        <input type="number" name="gpio" min="0" id="outputGpio">
        <label for="outputState">Initial GPIO State</label>
        <select id="outputState" name="state">
          <option value="0">0 = OFF</option>
          <option value="1">1 = ON</option>
        </select>
        <input type="submit" value="Create Output">
        <p><strong>Note:</strong> in some devices, you might need to refresh the page to see your newly created buttons or to remove deleted buttons.</p>
    </form></div>

    <script>
        function updateOutput(element) {
            var xhr = new XMLHttpRequest();
            if(element.checked){
                xhr.open("GET", "esp-outputs-action.php?action=output_update&id="+element.id+"&state=1", true);
            }
            else {
                xhr.open("GET", "esp-outputs-action.php?action=output_update&id="+element.id+"&state=0", true);
            }
            xhr.send();
        }

        function deleteOutput(element) {
            var result = confirm("Want to delete this output?");
            if (result) {
                var xhr = new XMLHttpRequest();
                xhr.open("GET", "esp-outputs-action.php?action=output_delete&id="+element.id, true);
                xhr.send();
                alert("Output deleted");
                setTimeout(function(){ window.location.reload(); });
            }
        }

        function createOutput(element) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "esp-outputs-action.php", true);

            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function() {
                if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                    alert("Output created");
                    setTimeout(function(){ window.location.reload(); });
                }
            }
            var outputName = document.getElementById("outputName").value;
            var outputBoard = document.getElementById("outputBoard").value;
            var outputGpio = document.getElementById("outputGpio").value;
            var outputState = document.getElementById("outputState").value;
            var httpRequestData = "action=output_create&name="+outputName+"&board="+outputBoard+"&gpio="+outputGpio+"&state="+outputState;
            xhr.send(httpRequestData);
        }

        // Función para actualizar el estado de los outputs cada 5 segundos
        function updateOutputsStatus() {
            showStatus("Actualizando...", "#fff3cd");
            
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "esp-outputs-action.php?action=get_outputs_status", true);
            
            xhr.onreadystatechange = function() {
                if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                    try {
                        var outputs = JSON.parse(this.responseText);
                        console.log("Datos recibidos:", outputs); // Debug
                        updateOutputsUI(outputs);
                        showStatus("Actualizado correctamente", "#d4edda");
                        updateLastUpdateTime();
                    } catch (e) {
                        console.log("Error parsing JSON response:", e);
                        console.log("Response text:", this.responseText); // Debug
                        showStatus("Error al procesar datos", "#f8d7da");
                    }
                } else if (this.readyState === XMLHttpRequest.DONE && this.status !== 200) {
                    console.log("Error en la petición HTTP:", this.status);
                    showStatus("Error en la petición HTTP: " + this.status, "#f8d7da");
                }
            }
            
            xhr.onerror = function() {
                console.log("Error de red en la petición AJAX");
                showStatus("Error de red", "#f8d7da");
            };
            
            xhr.send();
        }

        // Función para mostrar el estado de actualización
        function showStatus(message, color) {
            var indicator = document.getElementById("status-indicator");
            var statusText = document.getElementById("status-text");
            
            indicator.style.display = "block";
            indicator.style.backgroundColor = color;
            statusText.textContent = message;
        }

        // Función para actualizar la hora de la última actualización
        function updateLastUpdateTime() {
            var lastUpdate = document.getElementById("last-update");
            var now = new Date();
            lastUpdate.textContent = " - Última actualización: " + now.toLocaleTimeString();
        }

        // Función para actualizar la interfaz de usuario con los nuevos estados
        function updateOutputsUI(outputs) {
            var changes = 0;
            console.log("Buscando " + outputs.length + " outputs en el DOM..."); // Debug
            
            outputs.forEach(function(output) {
                var checkbox = document.getElementById(output.id);
                console.log("Buscando checkbox con ID: " + output.id); // Debug
                
                if (checkbox) {
                    console.log("✓ Checkbox encontrado para ID: " + output.id); // Debug
                    var currentState = checkbox.checked ? "1" : "0";
                    console.log("Estado actual: " + currentState + ", Estado nuevo: " + output.state); // Debug
                    
                    if (output.state != currentState) {
                        console.log("🔄 Actualizando output ID " + output.id + " de " + currentState + " a " + output.state); // Debug
                        checkbox.checked = (output.state == "1");
                        changes++;
                    } else {
                        console.log("✓ Estado ya está actualizado para ID: " + output.id); // Debug
                    }
                } else {
                    console.log("❌ No se encontró checkbox para output ID: " + output.id); // Debug
                    // Listar todos los checkboxes disponibles para debugging
                    var allCheckboxes = document.querySelectorAll('input[type="checkbox"]');
                    console.log("Checkboxes disponibles en el DOM:");
                    allCheckboxes.forEach(function(cb, index) {
                        console.log("  " + index + ": ID=" + cb.id + ", checked=" + cb.checked);
                    });
                }
            });
            
            if (changes > 0) {
                console.log("✅ Se actualizaron " + changes + " outputs");
            } else {
                console.log("ℹ️ No se requirieron cambios en los outputs");
            }
        }

        // Iniciar la actualización automática cada 5 segundos
        // console.log("Iniciando actualización automática cada 5 segundos"); // Debug
        // setInterval(updateOutputsStatus, 5000);
        
        // También ejecutar una vez al cargar la página
        // updateOutputsStatus();
    </script>
</body>
</html>
