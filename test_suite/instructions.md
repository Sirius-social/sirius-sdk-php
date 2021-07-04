## Instructions for configuring PHP interpreters in phpstorm

- Press Ctrl+Alt+S to open IDE settings and select PHP.
- On the PHP page that opens, click the Browse button next to the CLI Interpreter list.
- In the CLI Interpreters dialog that opens, click the Add button ("+") in the left-hand pane, then choose "From Docker, Vagrant, VM, WSL, Remote..." from the popup menu.
- In the dialog select the Docker Compose tab.
- Then, in the Configuration files specify the path to the docker-compose.yml file. docker-compose.yml file path: sirius-sdk-php/test_suite/docker-compose.yml.
- Then, in the Service choose dev and press OK button.

<img src="/Sirius-social/sirius-sdk-php/blob/feature_testing/docs/_static/php_instructions.gif?raw=true" alt="php_instructions.gif">