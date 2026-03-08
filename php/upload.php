<?php
/**
 * Helper de subida de archivos multimedia.
 * Uso: $rutas = mitos_upload_media($_FILES['media'], 'obra');
 */

if (!function_exists('mitos_upload_media')) {
    /**
     * Procesa un input file múltiple y guarda cada archivo en uploads/media/.
     *
     * @param array  $filesInput  Elemento de $_FILES (p.ej. $_FILES['media'])
     * @param string $prefijo     Prefijo para el nombre del archivo guardado
     * @return array{rutas: string[], errores: string[]}
     */
    function mitos_upload_media(array $filesInput, string $prefijo = 'archivo'): array
    {
        $extensionesImagen = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $extensionesVideo  = ['mp4', 'webm', 'mov'];
        $maxBytesImagen    = 8 * 1024 * 1024;   // 8 MB
        $maxBytesVideo     = 50 * 1024 * 1024;  // 50 MB

        $uploadDir = $GLOBALS['MITOS_ROOT'] . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $rutas   = [];
        $errores = [];

        // Normalizar estructura de $_FILES para input múltiple
        $archivos = [];
        if (is_array($filesInput['name'])) {
            foreach ($filesInput['name'] as $i => $nombre) {
                if ($filesInput['error'][$i] === UPLOAD_ERR_NO_FILE || $nombre === '') {
                    continue;
                }
                $archivos[] = [
                    'name'     => $nombre,
                    'type'     => $filesInput['type'][$i],
                    'tmp_name' => $filesInput['tmp_name'][$i],
                    'error'    => $filesInput['error'][$i],
                    'size'     => $filesInput['size'][$i],
                ];
            }
        } elseif (isset($filesInput['name']) && $filesInput['name'] !== '' && $filesInput['error'] !== UPLOAD_ERR_NO_FILE) {
            $archivos[] = $filesInput;
        }

        foreach ($archivos as $archivo) {
            if ($archivo['error'] !== UPLOAD_ERR_OK) {
                $errores[] = 'Error al subir "' . htmlspecialchars($archivo['name']) . '" (código ' . $archivo['error'] . ').';
                continue;
            }

            $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            $esImagen = in_array($ext, $extensionesImagen, true);
            $esVideo  = in_array($ext, $extensionesVideo, true);

            if (!$esImagen && !$esVideo) {
                $errores[] = '"' . htmlspecialchars($archivo['name']) . '" tiene una extensión no permitida.';
                continue;
            }

            $maxBytes = $esVideo ? $maxBytesVideo : $maxBytesImagen;
            if ($archivo['size'] > $maxBytes) {
                $max = $esVideo ? '50 MB' : '8 MB';
                $errores[] = '"' . htmlspecialchars($archivo['name']) . '" supera el tamaño máximo permitido (' . $max . ').';
                continue;
            }

            if (!is_uploaded_file($archivo['tmp_name'])) {
                $errores[] = 'Archivo "' . htmlspecialchars($archivo['name']) . '" no es válido.';
                continue;
            }

            $nombreFinal = $prefijo . '_' . uniqid('', true) . '.' . $ext;
            $destino     = $uploadDir . $nombreFinal;

            if (move_uploaded_file($archivo['tmp_name'], $destino)) {
                $rutas[] = [
                    'ruta' => 'uploads/media/' . $nombreFinal,
                    'tipo' => $esVideo ? 'video' : 'imagen',
                ];
            } else {
                $errores[] = 'No se pudo mover "' . htmlspecialchars($archivo['name']) . '" al servidor.';
            }
        }

        return ['rutas' => $rutas, 'errores' => $errores];
    }
}

if (!function_exists('mitos_delete_media_file')) {
    /**
     * Elimina el archivo físico de un medio dado su ruta relativa a la raíz.
     */
    function mitos_delete_media_file(string $rutaRelativa): void
    {
        $ruta = $GLOBALS['MITOS_ROOT'] . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $rutaRelativa), DIRECTORY_SEPARATOR);
        if (is_file($ruta)) {
            unlink($ruta);
        }
    }
}
