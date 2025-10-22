<div class="text-section-wrapper py-5">
  <div class="container px-3">
    <div class="text-section-inner row align-items-center mx-auto">
      
      <!-- Image Side -->
      <div class="col-md-6 text-section-image p-3">
        <img src="{{ asset('storage/ski-lift.jpg')}}" alt="Pro Skier" class="img-fluid rounded-4 shadow-lg">
      </div>
      
      <!-- Text Side -->
      <div class="col-md-6 p-4 text-section-content">
        <h2 class="fw-bold mb-3">
          <i class="fa-solid fa-person-skiing fa-lg me-2 "></i>
          <strong>Ιδιαίτερα Μαθήματα Σκι & Snowboard για Όλα τα Επίπεδα</strong>
        </h2>

        <h5 class="primary-color mb-4">
          <i class="fa-regular fa-calendar-check me-2"></i>
          Διάλεξε βουνό, ημερομηνία και ώρα - δες άμεσα διαθέσιμους προπονητές
        </h5>

        <p class="lead  opacity-90 mb-4">
          <i class="fa-solid fa-mountain-sun me-2 text-warning"></i>
          Κλείσε ιδιαίτερο μάθημα σκι ή snowboard με πιστοποιημένο προπονητή. 
          <br><i class="fa-solid fa-map-location-dot me-2 text-warning"></i>
          Διάλεξε βουνό, ημερομηνία και ώρα και δες σε πραγματικό χρόνο ποιος είναι διαθέσιμος. 
          <br><i class="fa-solid fa-users me-2 text-warning"></i>
          Υποστήριξη για beginner, intermediate και advanced, ατομικά ή μικρά γκρουπ.
          <br><i class="fa-solid fa-snowflake me-2 text-warning"></i>
          Προπονητές σε 
          @foreach($mountains as $m)
            {{$m->mountain_name}}
          @endforeach
          <br><i class="fa-solid fa-globe me-2 text-warning"></i>
          Κράτηση online, άμεση επιβεβαίωση.
        </p>

        <a href="#book-instructor-container" class="btn btn-primary mt-3 px-4 py-2 rounded-pill shadow-sm">
          <i class="fa-solid fa-calendar-plus me-2"></i> Κλείσε Μάθημα
        </a>
      </div>

    </div>
  </div>
</div>
