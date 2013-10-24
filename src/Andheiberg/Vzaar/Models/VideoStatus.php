<?php namespace Andheiberg\Vzaar\Models;

Class VideoStatus
{
	const PROCESSING = 1; //Processing not complete
	const AVAILABLE = 2; //Available (processing complete, video ready)
	const EXPIRED = 3; //Expired
	const ON_HOLD = 4; //On Hold (waiting for encoding to be available)
	const FAILED = 5; //Encoding Failed
	const ENCODING_UNAVAILABLE = 6; //Encoding Unavailable
	const NOT_AVAILABLE = 7; //n/a
	const REPLACED = 8; //Replaced
	const DELETED = 9; //Deleted
}