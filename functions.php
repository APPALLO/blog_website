function aktivite_kaydet($kullanici_id, $aktivite) {
    global $db;
    
    $ip_adresi = $_SERVER['REMOTE_ADDR'];
    
    $query = "INSERT INTO aktiviteler (kullanici_id, aktivite, ip_adresi) VALUES (:kullanici_id, :aktivite, :ip_adresi)";
    $stmt = $db->prepare($query);
    
    try {
        $stmt->execute([
            ':kullanici_id' => $kullanici_id,
            ':aktivite' => $aktivite,
            ':ip_adresi' => $ip_adresi
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Aktivite kaydetme hatası: " . $e->getMessage());
        return false;
    }
} 