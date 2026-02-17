<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
    <style>
        body {
            margin: 40px auto;
            max-width: 600px;
            font-family: georgia, times, serif;
            background: #fff;
            color: #222;
        }

        h1 {
            color: #000000;
            font-size: 41px;
            letter-spacing: -2px;
            line-height: 1em;
            font-family: helvetica, arial, sans-serif;
            border-bottom: 1px dotted #cccccc;
            padding-bottom: 10px;
        }

        h2 {
            color: #000000;
            font-size: 34px;
            letter-spacing: -2px;
            line-height: 1em;
            font-family: helvetica, arial, sans-serif;
        }

        a {
            color: #222222;
            font-family: georgia, times, serif;
            font-size: 24px;
            font-weight: normal;
            line-height: 1.2em;
            text-decoration: none;
        }

        a:hover {
            background-color: #BCFC3D;
        }

        input[type="text"] {
            color: #222222;
            font-family: georgia, times, serif;
            font-size: 24px;
            font-weight: normal;
            line-height: 1.2em;
            border: 1px solid #ccc;
            padding: 4px 8px;
        }

        input[type="submit"] {
            color: #222222;
            font-family: georgia, times, serif;
            font-size: 18px;
            cursor: pointer;
            padding: 4px 12px;
        }

        .item {
            display: inline-block;
            width: 400px;
        }
    </style>
</head>
<body>
<h1>My Todo-List App</h1>
