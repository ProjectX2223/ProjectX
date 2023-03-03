(function () {
 
  var apiKey = 'input your actual ChatGPT API key';

  // Append the ChatGPT chat window to the page.
  var chatWindow = document.createElement('iframe');
  chatWindow.setAttribute('id', 'chatgpt-chat-window');
  chatWindow.setAttribute(
    'src',
    'https://app.chatgpt.com/chat/?apiKey=' + apiKey
  );
  chatWindow.style.position = 'fixed';
  chatWindow.style.bottom = 0;
  chatWindow.style.right = 0;
  chatWindow.style.width = '320px';
  chatWindow.style.height = '480px';
  chatWindow.style.border = 'none';
  document.body.appendChild(chatWindow);
})();
