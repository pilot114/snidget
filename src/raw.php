<?php

#[Attribute(Attribute::TARGET_PROPERTY)]
class Binary
{
    // TODO: заменить format на длину в битах
    // TODO: сделать вложенность (ничего делать не надо, кроме как pack вручную?)
    public function __construct(public string $format)
    {
    }
}

class ICMPPacket
{
    public function __construct(
        #[Binary('C')] // 1 байт для типа сообщения ICMP
        public int $type,
        #[Binary('C')] // 1 байт для кода ICMP
        public int $code,
        #[Binary('n')] // 2 байта для контрольной суммы
        public int $checksum,
        #[Binary('n')] // 2 байта для идентификатора
        public int $identifier,
        #[Binary('n')] // 2 байта для номера последовательности
        public int $sequenceNumber,
        #[Binary('A*')] // Данные запроса (переменной длины)
        public string $data = '',
    ) {
        $this->checksum = $this->calculateChecksum();
    }

    /**
     * Рассчитывает контрольную сумму ICMP-пакета
     */
    private function calculateChecksum(): int
    {
        $packetWithoutChecksum = BinarySerializer::pack($this);

        // Замещаем контрольную сумму нулями для расчёта
        $packetWithoutChecksum[2] = "\x00";
        $packetWithoutChecksum[3] = "\x00";

        $bitLength = strlen($packetWithoutChecksum);
        $checksum = 0;

        for ($i = 0; $i < $bitLength; $i += 2) {
            $word = ord($packetWithoutChecksum[$i]) << 8 | ord($packetWithoutChecksum[$i + 1]);
            $checksum += $word;
        }

        $checksum = ($checksum >> 16) + ($checksum & 0xFFFF);
        $checksum += ($checksum >> 16);

        return ~$checksum & 0xFFFF;
    }
}

class IPPacket
{
    public function __construct(
        #[Binary('C')] // 4 бита версии + 4 бита длины заголовка
        public int $versionAndHeaderLength,
        #[Binary('C')] // 1 байт для типа сервиса
        public int $typeOfService,
        #[Binary('n')] // 2 байта для общей длины пакета
        public int $totalLength,
        #[Binary('n')] // 2 байта для идентификатора пакета
        public int $identification,
        #[Binary('n')] // 3 бита флагов и 13 бит смещения фрагмента
        public int $flagsAndFragmentOffset,
        #[Binary('C')] // 1 байт для времени жизни (TTL)
        public int $ttl,
        #[Binary('C')] // 1 байт для протокола
        public int $protocol,
        #[Binary('n')] // 2 байта для контрольной суммы заголовка
        public int $headerChecksum,
        #[Binary('N')] // 4 байта для IP-адреса источника
        public int $sourceIp,
        #[Binary('N')] // 4 байта для IP-адреса назначения
        public int $destinationIp,
        #[Binary('A*')] // Данные запроса (переменной длины)
        public string $data = '',
    ) {
        $this->headerChecksum = $this->calculateChecksum();
    }

    /**
     * Рассчитывает контрольную сумму заголовка IP
     */
    private function calculateChecksum(): int
    {
        $headerWithoutChecksum = BinarySerializer::pack($this);
        $headerWithoutChecksum[10] = "\x00";
        $headerWithoutChecksum[11] = "\x00";

        $checksum = 0;
        $bitLength = strlen($headerWithoutChecksum);

        for ($i = 0; $i < $bitLength; $i += 2) {
            $word = ord($headerWithoutChecksum[$i]) << 8 | ord($headerWithoutChecksum[$i + 1]);
            $checksum += $word;
        }

        $checksum = ($checksum >> 16) + ($checksum & 0xFFFF);
        $checksum += ($checksum >> 16);

        return ~$checksum & 0xFFFF;
    }
}

class BinarySerializer
{
    public static function pack(object $object): string
    {
        $binaryData = '';
        foreach ((new ReflectionClass($object))->getProperties() as $property) {
            $format = self::getPropertyFormat($property);
            if ($format === null) {
                throw new Exception("Format for property '{$property->getName()}' is not defined.");
            }
            $binaryData .= pack($format, $property->getValue($object));
        }
        return $binaryData;
    }

    public static function unpack(string $binaryData, string $className): object
    {
        $reflection = new ReflectionClass($className);
        $object = $reflection->newInstanceWithoutConstructor();
        $offset = 0;

        foreach ($reflection->getProperties() as $property) {
            $format = self::getPropertyFormat($property);
            if ($format === null) {
                throw new Exception("Format for property '{$property->getName()}' is not defined.");
            }
            $value = unpack($format, substr($binaryData, $offset));
            $size = self::getFormatSize($format, array_values($value)[0]);
            $offset += $size;
            $property->setValue($object, array_values($value)[0]);
        }

        return $object;
    }

    private static function getPropertyFormat(ReflectionProperty $property): ?string
    {
        $attributes = $property->getAttributes(Binary::class);
        if ($attributes !== []) {
            return $attributes[0]->newInstance()->format;
        }
        return null;
    }

    private static function getFormatSize(string $format, $value = null): int
    {
        return match ($format) {
            'C' => 1,
            'n' => 2,
            'N' => 4,
            'A*' => strlen($value),
            default => throw new Exception("Unknown format size for '{$format}'."),
        };
    }
}

function sendICMPRequest(string $host, ICMPPacket $packet): ?array
{
    // Проверяем привилегии для сырого сокета
    if (posix_geteuid() != 0) {
        die("Этот скрипт нужно запустить с правами суперпользователя (root).\n");
    }

    $socket = socket_create(AF_INET, SOCK_RAW, 1); // Протокол ICMP — это 1
    if (!$socket) {
        die('Не удалось создать сокет: ' . socket_strerror(socket_last_error()));
    }

    $packetData = BinarySerializer::pack($packet);

    echo sprintf("%s >>> %s\n", strlen($packetData), bin2hex($packetData));

    // Отправляем ICMP-запрос
    if (!socket_sendto($socket, $packetData, strlen($packetData), 0, $host, 0)) {
        die('Ошибка при отправке данных: ' . socket_strerror(socket_last_error()));
    }

    // Устанавливаем время ожидания
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, [
        "sec" => 1,
        "usec" => 0,
    ]);

    $startTime = microtime(true);
    $buffer = '';
    if (socket_recvfrom($socket, $buffer, 1024, 0, $sourceAddress, $sourcePort) === false) {
        die('Ошибка при получении данных: ' . socket_strerror(socket_last_error()));
    }

    echo sprintf("%s <<< %s\n", strlen($buffer), bin2hex($buffer));

    // TODO: response common format
    return [
        'source_ip' => $sourceAddress,
        'source_port' => $sourcePort,
        'response_time_ms' => (microtime(true) - $startTime) * 1000,
        'ip_header' => substr($buffer, 0, 20),
        'icmp_header' => substr($buffer, 20),
    ];
}

function sendIPPacket(string $host, IPPacket $packet): void
{
    if (!defined('IP_HDRINCL')) {
        define('IP_HDRINCL', 2);
    }

    // Создаём сырой сокет для отправки IP-пакета
    $socket = socket_create(AF_INET, SOCK_RAW, SOL_SOCKET);
    if (!$socket) {
        die('Ошибка создания сокета: ' . socket_strerror(socket_last_error()));
    }

    // Устанавливаем флаг IP_HDRINCL, чтобы система не добавляла собственный IP-заголовок
    if (!socket_set_option($socket, IPPROTO_IP, IP_HDRINCL, 1)) {
        die('Ошибка установки IP_HDRINCL: ' . socket_strerror(socket_last_error()));
    }

    $binaryData = BinarySerializer::pack($packet);

    // Отправляем пакет
    if (!socket_sendto($socket, $binaryData, strlen($binaryData), 0, $host, 0)) {
        die('Ошибка отправки данных: ' . socket_strerror(socket_last_error()));
    }

    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, [
        "sec" => 1,
        "usec" => 0,
    ]);

    echo "Пакет успешно отправлен!\n";

    $buffer = '';
    socket_recvfrom($socket, $buffer, 1024, 0, $sourceAddress, $sourcePort);
    echo "Получен ответ от: $sourceAddress\n";

    // Разбор IP-заголовка
    $ipHeader = substr($buffer, 0, 20);  // Первые 20 байт — IP-заголовок
    $ipData = unpack('CversionAndIHL/Ctos/nlength/nid/nflagsAndOffset/Cttl/Cprotocol/nchecksum/NsourceIp/NdestinationIp', $ipHeader);

    echo "Версия: " . ($ipData['versionAndIHL'] >> 4) . "\n";
    echo "TTL: " . $ipData['ttl'] . "\n";
    echo "Протокол: " . $ipData['protocol'] . "\n";
    echo "IP источника: " . long2ip($ipData['sourceIp']) . "\n";
    echo "IP назначения: " . long2ip($ipData['destinationIp']) . "\n";

    // Закрываем сокет
    socket_close($socket);
}

$request = new ICMPPacket(
    type: 8,
    code: 0,
    checksum: 0,
    // эта часть - для эхо-запрос/ответа
    identifier: random_int(0, 65535),
    sequenceNumber: 1,
    data: str_repeat("\x00", 32)
);
//$result = sendICMPRequest('142.251.39.78', $request);
//// $response = BinarySerializer::unpack($result['ip_header'], IPPacket::class);
//$response = BinarySerializer::unpack($result['icmp_header'], ICMPPacket::class);
//var_dump($request);
//var_dump($response);


$version = 4;         // IPv4
$ihl = 5;             // Длина заголовка в 32-битных словах (5 слов = 20 байт)
$flags = 2;           // DF (Don't Fragment)
$fragmentOffset = 0;  // Нет фрагментации

$ipPacket = new IPPacket(
    versionAndHeaderLength: ($version << 4) | $ihl,
    typeOfService: 0,                      // Стандартный приоритет
    totalLength: 40,                       // Длина пакета (20 байт заголовка + 20 байт данных)
    identification: random_int(0, 65535),  // Уникальный идентификатор пакета
    flagsAndFragmentOffset: ($flags << 13) | $fragmentOffset,
    ttl: 64,  // Стандартное время жизни
    protocol: 1,  // Протокол ICMP
    headerChecksum: 0,
    sourceIp: ip2long('192.168.1.100'),  // IP-адрес источника (например, локальная сеть)
    destinationIp: ip2long('8.8.8.8'),   // IP-адрес назначения (например, Google DNS)
    data: str_repeat("\x00", 20),
);

sendIPPacket('8.8.8.8', $ipPacket);
