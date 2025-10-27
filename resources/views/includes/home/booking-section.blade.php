<style>
    .booking-section{
        background: url(storage/bg_img_234.jpg) right / cover no-repeat;
    }
    @media (max-width:768px){
        .booking-section{
            background: url('{{ asset('storage/bg_img_234.jpg') }}')  no-repeat 63% 40% / cover;
        }
    }
</style>
<section class="booking-section" >
    <div class="text-container">
        <h5>{{__('auth.proponites_ski')}}</h5>
        <h1>{{__('auth.kleiste_mathima')}}</h1>
        <div class="underline"></div>
        <p>{{__('auth.kleiste_mathima_text')}}</p>
        <a href="#book-instructor-container" class="booking-btn btn btn-primary mt-3 px-4 py-2 rounded-pill shadow-sm">BOOKING</a>
    </div>

    <div class="image-container">
    
    </div>
</section>