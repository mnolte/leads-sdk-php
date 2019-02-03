#Lead Management System
The Websolve Lead Support System (LSS) is a system designed to receive leads from many different sources and provides a single interface to manage and prepare these leads in order to be able to process them further in Autoline. This document is targeted at developers who would like to send leads to the LLS using our API.

## Installation
Install the latest version with
```bash
$ composer require websolve/leads-sdk-php
```

##Access and security
Access to this API is limited on an IP basis in combination with a ‘provider code’. Please request this code by giving your IP address to the LSS administrator. The WSDL is available at:
- https://www.websolve.nl/webservices/automotiveLeads.php?wsdl (production)
- http://www.websolve-dev.nl/webservices/automotiveLeads.php?wsdl (test)

##Methods available
The following methods are available in this API:
- [getLeadHeaders](docs/01-methods.md#getleadheaders)
- [setLead](docs/01-methods.md#setlead)
- [getLead](docs/01-methods.md#getlead)
- [getLeadBulk](docs/01-methods.md#getleadbulk)