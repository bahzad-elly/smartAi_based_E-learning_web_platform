<!-- Floating AI Chatbot Widget (Part 12) -->
<div class="chatbot-toggle-btn" id="chatbot-toggle-btn" title="Ask AI Assistant">
   <i class="fas fa-comment-dots"></i>
</div>

<div class="chatbot-container" id="chatbot-container">
   <!-- Chat Header -->
   <div class="chatbot-header">
      <div class="chatbot-header-info">
         <img src="images/default.png" alt="EducaBot" id="chatbot-avatar">
         <div>
            <h4>EducaBot</h4>
            <span>AI Learning Assistant</span>
         </div>
      </div>
      <button class="chatbot-close-btn" id="chatbot-close-btn" title="Minimize">
         <i class="fas fa-minus"></i>
      </button>
   </div>

   <!-- Message Log -->
   <div class="chatbot-messages-area" id="chatbot-messages-area">
      <div class="chatbot-bubble bot">
         Hi! I am **EducaBot**, your AI assistant. How can I help you today? You can ask me to explain lessons, recommend courses, or explain certificate verification!
      </div>
   </div>

   <!-- Suggestions Grid -->
   <div class="chatbot-suggestions" id="chatbot-suggestions">
      <button type="button" class="chatbot-suggestion-pill" data-question="Recommend courses">Recommend courses</button>
      <button type="button" class="chatbot-suggestion-pill" data-question="How to verify certificate?">Verify certificate</button>
      <button type="button" class="chatbot-suggestion-pill" data-question="Explain a lesson">Explain a lesson</button>
   </div>

   <!-- Send Form -->
   <form class="chatbot-input-form" id="chatbot-send-form" autocomplete="off">
      <input type="text" id="chatbot-message-input" placeholder="Ask anything..." required>
      <button type="submit" class="chatbot-send-btn-widget" title="Send">
         <i class="fas fa-paper-plane"></i>
      </button>
   </form>
</div>
