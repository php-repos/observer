<?php

namespace PhpRepos\Observer\API;

/**
 * Inquiry signal represents a request for information.
 *
 * Inquiries ask questions or request data from the system. Handlers can
 * respond by returning signals containing the requested information.
 */
class Inquiry extends Signal {}
