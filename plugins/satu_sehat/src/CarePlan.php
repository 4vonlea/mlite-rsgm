<?php

namespace SatuSehat\Src;

class CarePlan
{
    private $patient_id;
    private $uuid_encounter;
    private $uuid_careplan;
    private $instruction;
    private $title;
    private $no_ktp_dokter;

    public function __construct($patient_id,$uuid_encounter,$uuid_careplan,$instruction,$title,$no_ktp_dokter)
    {
        $this->patient_id = $patient_id;
        $this->uuid_encounter = $uuid_encounter;
        $this->uuid_careplan = $uuid_careplan;
        $this->instruction = $instruction;
        $this->title = $title;
        $this->no_ktp_dokter = $no_ktp_dokter;
    }

    public function toJson()
    {
        return [
            "resourceType" => "CarePlan",
            "status" => "active",
            "intent" => "plan",
            "category" => [
                [
                    "coding" => [
                        [
                            "system" => "http://snomed.info/sct",
                            "code" => "736271009",
                            "display" => "Outpatient care plan"
                        ]
                    ]
                ]
            ],
            "title" => $this->title,
            "description" => $this->instruction,
            "subject" => [
                "reference" => "Patient/".$this->patient_id,
            ],
            "encounter" => [
                "reference" => "urn:uuid:".$this->uuid_encounter,
            ],
            "author" => [
                "reference" => "Practitioner/".$this->no_ktp_dokter,
            ]
        ];
    }
    public function toJsonBundle()
    {
        return [
            "fullUrl" => "urn:uuid:" . $this->uuid_careplan,
            "resource" => $this->toJson(),
            "request" => [
                "method" => "POST",
                "url" => "CarePlan"
            ]
        ];
    }
}