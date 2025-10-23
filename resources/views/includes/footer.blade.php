<footer class="footer">
  <div class="footer-container">
    <div class="footer-section about">
      <h3>Σχετικά</h3>
            <p>Μαθήματα Σκι & Snowboard με Πιστοποιημένους Προπονητές - Κλείσε Online</p>
    </div>

    <div class="footer-section links">
      <h3>Χρήσιμα Links</h3>
      <ul>
        @if (Auth::user())
            <li><a href="{{ route('profile', Auth::user()->id) }}">{{ __('auth.my_profile') }}</a></li>
            <li><a href="{{ route('profile.date', Auth::user()->id) }}">Το Πρόγραμμά μου</a></li>
        @else
            <li><a href="{{ route('register') }}">Είσαι Προπονητής; Κάνε εγγραφή</a></li>
            <li><a href="{{ route('login') }}">Σύνδεση</a></li>
            <li><a href="#book-instructor-container">Κλείσε μάθημα</a></li>
        @endif
      </ul>
    </div>

    <div class="footer-section contact">
      <h3>Επικοινωνία</h3>
      <p>Email: info@ski-lessons.gr</p>
      <p>Phone: +1 (555) 123-4567</p>
      <div class="socials">
        ❄️⚪❄️⚪❄️⚪❄️
        {{-- <a href="#" class="social-icon">🌐</a>
        <a href="#" class="social-icon">🐦</a>
        <a href="#" class="social-icon">📘</a>
        <a href="#" class="social-icon">📸</a> --}}
      </div>
    </div>
  </div>

  <div class="footer-bottom">
    <p>© 2025 Ski-lessons. All rights reserved.</p>
  </div>
</footer>