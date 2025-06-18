<?php
    include_once('esp-database.php');
    
    echo "<h2>Prueba de API - Estados de Outputs</h2>";
    
    // Probar la función getAllOutputs
    $result = getAllOutputs();
    
    if ($result) {
        echo "<h3>Outputs encontrados:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Board</th><th>GPIO</th><th>Estado</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row["id"] . "</td>";
            echo "<td>" . $row["name"] . "</td>";
            echo "<td>" . $row["board"] . "</td>";
            echo "<td>" . $row["gpio"] . "</td>";
            echo "<td>" . $row["state"] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No se encontraron outputs o error en la consulta.</p>";
    }
    
    // Probar la respuesta JSON
    echo "<h3>Respuesta JSON:</h3>";
    $result2 = getAllOutputs();
    $outputs = array();
    if ($result2) {
        while ($row = $result2->fetch_assoc()) {
            $outputs[] = array(
                "id" => $row["id"],
                "name" => $row["name"],
                "board" => $row["board"],
                "gpio" => $row["gpio"],
                "state" => $row["state"]
            );
        }
    }
    
    echo "<pre>" . json_encode($outputs, JSON_PRETTY_PRINT) . "</pre>";
?> 