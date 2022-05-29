<?php
require './vendor/autoload.php';

use Jose\Component\Signature\Serializer\JWSSerializerManager;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\JWSBuilder;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class SHC
{
    private static $resourceNo = 0;
    private static $funcs = [
        //'type' => 'function'
        'Immunization' => 'self::genImmunizationData'
    ];

    /**
     * @param string $family 성
     * @param string $given 이름
     * @param string $birth 생년월일 (YYYY-MM-DD)
     * @param array|bool $infos 접종정보
     */
    public function __construct(
        private $issuer,
        private $family,
        private $given,
        private $birth,
        private array $infos
    )
    {}

    private static function resourceNo(): String
    {
        return strval(self::$resourceNo++);
    }

    private function genPatientData(): void
    {
        $given = explode(' ', $this->given);
        $this->patient = [
            'fullUrl' => 'resource:'.self::resourceNo(),
            'resource' => [
                'resourceType' => 'Patient',
                'id' => md5($_SERVER['REMOTE_ADDR']), // you can customize this as you want
                'name' => [['family' => $this->family, 'given' => $given]],
                'birthDate' => $this->birth
            ]
        ];
    }

    /**
     * @param array $immset = ['cvx' => '', 'date' => '', 'lot' => '', 'perform' => '']
     */
    private static function genImmunizationData(array $immset): Array
    {
        $imm = [
            'fullUrl' => 'resource:'.self::resourceNo(),
            'resource' => [
                'resourceType' => 'Immunization',
                'status' => 'completed',
                'vaccineCode' => [
                    'coding' => [['system' => 'http://hl7.org/fhir/sid/cvx', 'code' => $immset['cvx']]]
                ],
                'patient' => ['reference' => 'resource:0'],
                'occurrenceDateTime' => $immset['date'],
                'lotNumber' => $immset['lot']
            ]
        ];

        if($immset['perform'])
            $imm['resource']['performer'] = [['actor' => ['display' => $immset['perform']]]];
        

        return $imm;
    }

    private function get_FHIR_bundle(): void
    {
        $entry = [$this->patient];
        foreach ($this->infos as $i){
            array_push($entry, call_user_func(self::$funcs[$i['type']], $i));
        }

        $this->FHIR = [
            'resourceType' => 'Bundle',
            'type' => 'collection',
            'entry' => $entry
        ];
    }

    /**
     * @return string QR code encoded in base64
     */
    public function genshc(): string
    {

        $this->genPatientData();
        $this->get_FHIR_bundle();
        $this->vc = [
            'iss' => 'https://link.to/issuer', // MUST NOT have trailing slash
            'nbf' => intval($_SERVER['REQUEST_TIME']),
            'vc' => [
                'type' => [
                    "https://smarthealth.cards#health-card",
                    "https://smarthealth.cards#immunization",
                    "https://smarthealth.cards#covid19" // necessary for covid19 vaccine
                ],
                'credentialSubject' => [
                    'fhirVersion' => '4.0.1',
                    'fhirBundle' => $this->FHIR
                ]
            ]
        ];
        
        $this->key = [
            'alg' => 'ES256',
            'crv' => 'P-256',
            'd' => 'your D',
            'kty' => 'EC',
            'use' => 'sig',
            'x' => 'your X',
            'y' => 'your Y'
            'kid' => 'your kid'
        ];
        $key = JWK::createFromJson(json_encode($this->key));

        $jwsBuilder = new JWSBuilder(new AlgorithmManager([new ES256()]));
        $jws = $jwsBuilder
            ->create()
            ->withPayload(gzdeflate(json_encode($this->vc)))
            ->addSignature($key, ['kid' => $this->key['kid'], 'zip' => 'DEF', 'alg' => 'ES256'])
            ->build();
        
        $token = (new JWSSerializerManager([new CompactSerializer()]))->serialize('jws_compact', $jws);
        $shc = 'shc:/' . implode('', array_map(fn($c) => str_pad(ord($c) - 45, 2, "0", STR_PAD_LEFT),str_split($token)));
        
        $opt = new QROptions([
            'version' => QRCode::VERSION_AUTO
        ]);
        
        return (new QRCode($opt))->render($shc);
    }
}
