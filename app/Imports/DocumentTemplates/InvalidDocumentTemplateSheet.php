<?php

namespace App\Imports\DocumentTemplates;

use RuntimeException;

/**
 * The uploaded file cannot be read as a document template sheet at all. The
 * message is meant for the person who uploaded it.
 */
class InvalidDocumentTemplateSheet extends RuntimeException {}
