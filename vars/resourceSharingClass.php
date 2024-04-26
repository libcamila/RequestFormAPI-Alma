<?php
class ResourceSharingRequestObject
{
    public $owner, $agree_to_copyright_terms;
    public $format, $citation_type, $title, $chapter_title, $journal_title, $oclc_number, $volume, $issue, $issn, $isbn, $pmid, $doi, $author, $source, $year, $pages, $allow_other_formats, $pickup_location, $level_of_service, $preferred_send_method, $last_interest_date, $note;
    public function __construct()
    {
        $this->note = '';
        $this->owner = "WOU";
        $this->agree_to_copyright_terms = true;
        $this->allow_other_formats = true;
    }
    function set_param($name, $value)
    {
        $this->{$name} = $value;
    }
    function set_value($name, $value)
    {
        if (!isset($this->{$name})) {
            $this->{$name} = new stdClass();
        }
        $this->{$name}->value =  $value;
    }
    function set_format($format)
    {
        if (!isset($this->format)) {
            $this->format = new stdClass();
        }
        $this->format->desc = $format;
        $this->format->value =  strtoupper($format);
    }
    function set_citation_type($format)
    {
        if (!isset($this->citation_type)) {
            $this->citation_type = new stdClass();
        }
        $this->citation_type->desc = $format == 'Digital' ? 'Article' : 'Book';
        $this->citation_type->value = $format == 'Digital' ? 'CR' : 'BK';
    }
    function set_preferred_send_method($method)
    {
        if (!isset($this->preferred_send_method)) {
            $this->preferred_send_method = new stdClass();
        }
        $this->preferred_send_method->desc = true;
        $this->preferred_send_method->value =  $method;
    }
    function set_pickup_location($pickupLibrary)
    {
        global $pickupLibraries;
        if (!isset($this->pickup_location)) {
            $this->pickup_location = new stdClass();
        }
        $this->pickup_location->desc = $pickupLibraries[$pickupLibrary];
        $this->pickup_location->value =  $pickupLibrary;
    }
    function set_level_of_service($level_of_service)
    {
        $this->level_of_service->value =  $level_of_service;
    }
}
class holdRequest
{
    public $request_type, $description, $pickup_location_type, $pickup_location_library, $pickup_location_circulation_desk, $pickup_location_institution, $material_type, $comment, $mms_id;
    public function __construct()
    {
        $this->request_type = "HOLD";
        $this->pickup_location_type = "LIBRARY";
        $this->pickup_location_circulation_desk = null;
        $this->pickup_location_institution = null;
        $this->material_type = new stdClass();
        $this->material_type->value = null;
    }
    function set_param($name, $value)
    {
        $this->{$name} = $value;
    }
    function set_value($name, $value)
    {
        $this->{$name}->value =  $value;
    }
}
