<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

    <style type="text/css" rel="stylesheet" media="all">
        @import url('https://fonts.googleapis.com/icon?family=Montserrat:400,700');
        /* Media Queries */
        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
            }
        }

        a {
            color: #70b29c!important;
        }

        a:hover {
            color: #000;
        }

        a.button {
            color: #fff!important;
        }
    </style>
</head>

<?php

$style = [
    /* Layout ------------------------------ */

    'body' => 'margin: 0; padding: 0; width: 100%; background-color: #F5F5F5;',
    'email-wrapper' => 'width: 100%; margin: 0; padding: 0; background-color: #F5F5F5;',

    /* Masthead ----------------------- */

    'email-masthead' => 'padding: 25px 0; text-align: center;',
    'email-masthead_name' => 'font-size: 16px; font-weight: bold; color: #2F3133; text-decoration: none; text-shadow: 0 1px 0 white;',

    'email-body' => 'width: 100%; margin: 0; padding: 0; border-top: 1px solid #EDEFF2; border-bottom: 1px solid #EDEFF2; background-color: #FFF;',
    'email-body_inner' => 'width: auto; max-width: 700px; margin: 0 auto; padding: 0;',
    'email-body_cell' => 'padding: 35px;',

    'email-footer' => 'width: auto; max-width: 700px; margin: 0 auto; padding: 0; text-align: center;',
    'email-footer_cell' => 'color: #AEAEAE; padding: 35px; text-align: center;',

    /* Body ------------------------------ */

    'body_action' => 'width: 100%; margin: 30px auto; padding: 0; text-align: center;',
    'body_sub' => 'margin-top: 25px; padding-top: 25px; border-top: 1px solid #EDEFF2;width: 100%;',

    /* Type ------------------------------ */

    'anchor' => 'color: #70b29c;',
    'header-1' => 'margin-top: 15px; color: #2F3133; font-size: 19px; font-weight: bold; text-align: left;',
    'paragraph' => 'margin-top: 0; color: #74787E; font-size: 16px; line-height: 1.5em;',
    'paragraph-sub' => 'margin-top: 0; color: #74787E; font-size: 12px; line-height: 1.5em;',
    'paragraph-center' => 'text-align: center;',

    /* Buttons ------------------------------ */

    'button' => 'display: block; display: inline-block; width: 200px; min-height: 20px; padding: 10px;
                 background-color: #3869D4; border-radius: 3px; color: #ffffff; font-size: 16px; font-weight: 400; line-height: 36px; height: 36px;
                 text-align: center; text-decoration: none; -webkit-text-size-adjust: none;box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);',

    'button--green' => 'background-color: #697D99;',
    'button--red' => 'background-color: #dc4d2f;',
    'button--blue' => 'background-color: #70b29c;',
    'row-odd' => 'background-color: #DBE5F1;',
    'row-even' => 'background-color: white;',

    'primary-button' => "display: block; margin: 0 auto; width: 200px; min-height: 20px; padding: 10px; background-color: #0f81cc; border-radius: 3px; color: #ffffff; font-size: 16px; font-weight: 400; line-height: 36px; height: 36px; text-align: center; text-decoration: none; -webkit-text-size-adjust: none;box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);"

];
?>

<?php $fontFamily = 'font-family: Montserrat, "Helvetica Neue", Helvetica, Arial, sans-serif'; ?>

<body style="{{ $style['body'] }}">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="{{ $style['email-wrapper'] }}" align="center">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <!-- Logo -->
                    <tr>
                        <td style="{{ $style['email-masthead'] }}">
                            <img src="https://funinatl.nyc3.digitaloceanspaces.com/site/funinatl-logo.jpg" width="300" />
                        </td>
                    </tr>

                    <!-- Email Body -->
                    <tr>
                        <td style="{{ $style['email-body'] }}" width="100%">
                            <table style="{{ $style['email-body_inner'] }}" align="center" width="700" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="{{ $fontFamily }} {{ $style['email-body_cell'] }}">
                                        <!-- Greeting -->
                                        <h1 style="{{ $style['header-1'] }}">
                                            Hello {{ $submission->name }},
                                        </h1>

                                        <p style="{{ $style['paragraph'] }}">
                                            Thank you for submitting a contact form request! Below is the reply to your message:
                                        </p>

                                        <p style="{{ $style['paragraph'] }}">
                                            {!! nl2br($reply) !!}
                                        </p>

                                        <!-- Salutation -->
                                        <p style="{{ $style['paragraph'] }}">
                                            Sincerely,<br />
                                            Charlie Page<br />
                                            FunInATL
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>