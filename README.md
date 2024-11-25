# WPORG GP Engagement

This plugin, designed for https://translate.wordpress.org/, adds some 
notifications to try to engage the translators.

## First translation approved  

Sends an email notification to a translator when her first translation 
is approved.

<img src="https://github.com/user-attachments/assets/39e5f108-dfc7-40c4-92b6-ffcf80b75dd3" width="1000px">

## Translation milestone

Sends an email notification to a translator when she achieves a milestone:
a number of approved translations.

![image](https://github.com/user-attachments/assets/4ca40c3e-d1eb-4571-8189-695029a2f079)

## Translation anniversary

Sends an email notification to a translator when it is her translation anniversary.

![image](https://github.com/user-attachments/assets/360934aa-0cc4-43a4-97b1-c5ca1f8ac5f3)

You can send these notifications with this WP-CLI command:

```
wp wporg-translate engagement-anniversary --url=translate.wordpress.org
```

## Inactive user

Sends an email notification to a translation without activity in the last year.

![image](https://github.com/user-attachments/assets/3f5e8954-48d5-4877-a3e1-4684bc06f5ab)

You can send these notifications with this WP-CLI command:

```
wp wporg-translate engagement-inactive --url=translate.wordpress.org
```

## Translation consistency

Sends an email notification to a translator that has been translating the last: 48, 24, 12 and 6 months.

![image](https://github.com/user-attachments/assets/0a079765-f823-42d1-8992-ca6721092a4e)

You can send these notifications with this WP-CLI command:

```
wp wporg-translate engagement-consistency --url=translate.wordpress.org
```
