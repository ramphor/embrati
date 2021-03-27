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

## Display rating only

You need show star rating only please call below method
This feature use css-star-rating to show stars. You can check documnent at here: https://github.com/BioPhoton/css-star-rating

```
$options = array(
  'max' => 5,
  'use_svg' => true,
  'rating' => 2.5 // This is rating value to show stars
);
$embrati->display('rating_id', $options);
```
