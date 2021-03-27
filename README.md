# embrati
Embed rating

# Install

```
composer require ramphor/embrati
```


# Usage

## Create Embrati instance

```
$embrati  = Embrati::getInstance('your_instance_name');
```

## Register Javascript scripts.

```
// Register script for frontend
$embrati->registerScripts();

// Register script for admin
$embrati->registerAdminScripts();
```

## Create rating UI

```
// The options is RaterJS options. You can see list options at here: https://auxiliary.github.io/rater/
$options = array();
$embrati->create('rating_id', $options);
```
