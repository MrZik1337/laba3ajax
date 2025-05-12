<?php

require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $query_type = $_POST['query_type'] ?? '';

    try {
        switch ($query_type) {
            case 'wards_by_nurse':
                header('Content-Type: text/html; charset=UTF-8');
                $nurse_name = $_POST['nurse_name'] ?? '';
                if ($nurse_name) {
                    $sql = "SELECT W.name AS ward_name
                            FROM WARD W
                            JOIN NURSE_WARD NW ON W.ID_Ward = NW.FID_Ward
                            JOIN NURSE N ON NW.FID_Nurse = N.ID_Nurse
                            WHERE N.name = :nurse_name";

                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':nurse_name', $nurse_name);
                    $stmt->execute();
                    $wards = $stmt->fetchAll();

                    if ($wards) {
                        echo "<h3>Палати, де чергує " . htmlspecialchars($nurse_name) . ":</h3>";
                        echo "<ul>";
                        foreach ($wards as $ward) {
                            echo "<li>" . htmlspecialchars($ward['ward_name']) . "</li>";
                        }
                        echo "</ul>";
                    } else {
                        echo "<p>Медсестра '" . htmlspecialchars($nurse_name) . "' не знайдена або не чергує в жодній палаті.</p>";
                    }
                } else {
                    echo "<p>Будь ласка, введіть ім'я медсестри.</p>";
                }
                break;

            case 'nurses_by_department':
                header('Content-Type: text/xml; charset=UTF-8');
                $department_name = $_POST['department_name'] ?? '';
                if ($department_name !== '') {
                    $sql = "SELECT name, shift
                            FROM NURSE
                            WHERE department = :department";

                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':department', $department_name, PDO::PARAM_INT);
                    $stmt->execute();

                    $nurses = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $xml = new SimpleXMLElement('<nurses/>');
                    if ($nurses) {
                         foreach ($nurses as $nurse) {
                             $nurse_xml = $xml->addChild('nurse');
                             $nurse_xml->addChild('name', htmlspecialchars($nurse['name']));
                             $nurse_xml->addChild('shift', htmlspecialchars($nurse['shift']));
                         }
                    }
                    echo $xml->asXML();

                } else {
                     $xml = new SimpleXMLElement('<nurses/>');
                     echo $xml->asXML();
                }
                break;

            case 'duties_by_shift':
                header('Content-Type: application/json; charset=UTF-8');
                $shift_name = $_POST['shift_name'] ?? '';
                $allowed_shifts = ['First', 'Second', 'Third'];
                if ($shift_name && in_array($shift_name, $allowed_shifts)) {
                    $sql = "SELECT N.name AS nurse_name, W.name AS ward_name
                            FROM NURSE N
                            JOIN NURSE_WARD NW ON N.ID_Nurse = NW.FID_Nurse
                            JOIN WARD W ON NW.FID_Ward = W.ID_Ward
                            WHERE N.shift = :shift_name";

                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':shift_name', $shift_name);
                    $stmt->execute();

                    $duties = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    echo json_encode($duties);

                } else {
                    echo json_encode([]);
                }
                break;

            default:
                header('Content-Type: text/plain; charset=UTF-8', true, 400);
                echo "Невідомий тип запиту.";
                break;
        }
    } catch (\PDOException $e) {
        header('Content-Type: text/plain; charset=UTF-8', true, 500);
        echo "Помилка бази даних: " . $e->getMessage();
    } catch (\Exception $e) {
        header('Content-Type: text/plain; charset=UTF-8', true, 500);
        echo "Невідома помилка: " . $e->getMessage();
    }
} else {
    header('Content-Type: text/plain; charset=UTF-8', true, 405);
    echo "Цей файл призначений для обробки POST-запитів.";
}

?>