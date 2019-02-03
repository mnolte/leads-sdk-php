# Methods
- [getLeadHeaders](#getleadheaders)
- [setLead](#setlead)
- [getLead](#getlead)
- [getLeadBulk](#getleadbulk)

## getLeadHeaders
The method getLeadHeaders is used to retrieve all fields that can be used while submitting a lead to the LSS.

### Arguments
Argument | Type |  Description
--- | --- | ---
providerCode | string | To be supplied by LSS administrator, always linked to one IP address

### Returns
The method returns an XML with all fields available for submitting a lead in the following format:
```xml
<?xml version="1.0" encoding="utf-8"?>
<headers>
    <automotive_leads>
        <refID>
        <name>refID</name>
        <max_length>5</max_length>
        <length>11</length>
        <type>3</type>
        <type_name>INT</type_name>
        <values></values>
        <decimals>0</decimals>
        <IS_NULLABLE>YES</IS_NULLABLE>
        </refID>
        ...
    </automotive_leads>
    <automotive_leads_info_company>
        ...
    </automotive_leads_info_company>
    <automotive_leads_info_customer>
        ...
    </automotive_leads_info_customer>
    <automotive_leads_info_vehicle>
        ...
    </automotive_leads_info_vehicle>
</headers>
```

### Example usage
```php
$client = new \Websolve\Leads\Client();
$result = $client->getLeadHeaders($providerCode);
```

## setLead
The method setLead can be used to submit a new lead to the LSS. All available fields can be retrieved by using the getLeadHeaders method. 

### Arguments
Argument | Type |  Description
--- | --- | ---
providerCode | string | To be supplied by LSS administrator, always linked to one IP address
leadData | string | An XML file

```xml
<?xml version="1.0" encoding="utf-8"?>
<lead>
    <automotive_leads>
        <refID>ABC-987654</refID>
        <remarks><![CDATA[<p>Dit is een test!</p>]]></remarks>
    </automotive_leads>
    <automotive_leads_info_vehicle>
        <regno>71-XSP-1</regno>
    </automotive_leads_info_vehicle>
    <automotive_leads_info_customer>
        <first_name>Bart</first_name>
        <surname>Simpson</surname >
    </automotive_leads_info_customer>
    <automotive_leads_info_company />
</lead>
```

### Returns
#### Failure
```xml
<?xml version="1.0" encoding="utf-8"?>
<lead>
    <request_status>request processed</request_status>
    <created>0</created>
    <error>refID already exists</error>
</lead>
```
#### Success
```xml
<?xml version="1.0" encoding="utf-8"?>
<lead>
    <request_status>request processed</request_status>
    <created>1</created>
    <returnID>218</returnID>
</lead>
```

### Example usage
```php
$client = new \Websolve\Leads\Client();
$result = $client->setLead($providerCode, $leadData);
```

## getLead
The method getLead is used to get the status of the lead within LSS.

### Arguments
Argument | Type |  Description
--- | --- | ---
providerCode | string | To be supplied by LSS administrator, always linked to one IP address
refID | string | The refID used to submit the lead

### Returns
The method returns an XML that tells about the different statuses a lead has had.
```xml
<?xml version="1.0" encoding="utf-8"?>
<lead>
    <lead_created>2015-02-05 18:09:05</lead_created>
    <first_followup>2015-02-05 22:57:05</first_followup>
    <autoline_contacts>
        <contact_1>
            <date>2015-02-17</date>
            <label>order</label>
            <code>GOR</code>
            <vehicle_type>used</vehicle_type>
        </contact_1>
        <contact_2>
            <date>2015-02-24</date>
            <label></label>
            <code>GTR</code>
            <vehicle_type></vehicle_type>
        </contact_2>
    </autoline_contacts>
</lead>
```

### Example usage
```php
$client = new \Websolve\Leads\Client();
$result = $client->getLead($providerCode, $refID);
```

## getLeadBulk
The method getLead is used to get the status of the lead within LSS.

### Arguments
Argument | Type |  Description
--- | --- | ---
providerCode | string | To be supplied by LSS administrator, always linked to one IP address
datetimeStart | string | The start date of the selection (accepts almost any format)
datetimeEnd | string | The end date of the selection (accepts almost any format)

### Returns
The method returns an XML that tells about the different statuses the leads have had.
```xml
<?xml version="1.0" encoding="utf-8"?>
<leads>
    <lead>
        <refID>1234</refID>
        <lead_created>2015-01-07 19:00:50</lead_created>
        <first_followup>2015-01-08 07:24:12</first_followup>
    </lead>
    <lead>
        <refID>4321</refID>
        <lead_created>2015-01-05 21:30:06</lead_created>
        <first_followup>2015-01-06 02:18:06</first_followup>
    </lead>
</leads>
```

### Example usage
```php
$client = new \Websolve\Leads\Client();
$result = $client->getLeadBulk($providerCode, $datetimeStart, $datetimeEnd);
```
