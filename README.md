# MultiVersion-PMMP
 Allow players of different protocols to connect and play together without a hassle.

## Supported Versions: (PMMP Versions)
 **Please note:** Versions with a [B] next to it are unofficial builds of PMMP supported by this plugin <br />
    - v1.12.0 **Incomplete**<br />
    - v1.14.0

## Information
 **How do I use this plugin?** <br />
  Simply drag the plugin to your plugins directory and the plugin will automatically detect what versions to support.

## Configuration
 **I only want to support x version(s)** <br />
  While we don't allow you to remove support for the Servers protocol version, we allow you to restrict it to the versions you wish. Simply modify the config in your plugin data folder.<br />

  If I would only like to support 1.14.0 on 1.13.0 PM, my config would look as follows:
  ```yml
  support-versions: ['1.14.0']
  ```
  However if i would like to support 1.12.0 and 1.14.0 on 1.13.0 PM, my config would look as follows:
  ```yml
  support-versions: ['1.12.0', '1.14.0']
  ```

  **Please Note:** This plugin contains a lot of hacky methods to produce multiversion support for players.
