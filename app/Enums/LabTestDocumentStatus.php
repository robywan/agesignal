<?php

namespace App\Enums;

enum LabTestDocumentStatus: string
{
    case Pending = 'pending';
    case Extracted = 'extracted';
    case Parsed = 'parsed';
}
