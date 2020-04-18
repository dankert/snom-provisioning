# SNOM Phone provisioning

Snom phones can be provisioned by a custom settings url.

This script is able to provision
- the network configuration
- identities
- phonebook

The phones are configured in a simple YAML file.

## Provision phonebook
The phonebook is read from a CSV file. The CSV file format is the google contacs CSV file format. So you can provision your phonebook with your google contacts.

Provisioning of user accounts is not implemented yet, but planned ;)

## Install
- Copy this application to a webserver with PHP 7
- Customize the `devices.yml`
- Call the start page in your browser
- Copy the settings url and paste it in the web interface of your snom phones under _advanced_ - _update_.
- Restart the phone 