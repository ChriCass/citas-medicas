<style>
  .hero-section {
    min-height: 70dvh;
    background-image: url('/assets/img/homepage-all.svg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;



    & .container-index {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 2rem !important;
      height: 70dvh;
    }

    .content {
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 1rem;
      height: 100%;
      width: 60%;

      @media (max-width: 576px) {
        width: 100%;
      }



      .title {
        font-size: clamp(2rem, 5vw, 3.5rem);
        font-weight: 700;
        color: white;
        margin: 0 !important;
        line-height: 1.2;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      }

      .subtitle {
        font-size: clamp(1rem, 3vw, 1.3rem);
        color: rgba(255, 255, 255, 0.9);
        margin: 0 !important;
        line-height: 1.4;
        font-weight: 400;
      }

      .cta-buttons {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        margin: 0 !important;


        .btn {
          padding: 0.8rem 1.5rem;
          border-radius: 8px;
          text-decoration: none;
          font-weight: 600;
          font-size: 1rem;
          transition: all 0.3s ease;
          border: none;
          cursor: pointer;
          box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);

          &.btn-primary {
            background-color: #4A90E2;
            color: white;

            &:hover {
              background-color: #357ABD;
              transform: translateY(-2px);
              box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            }
          }

          &.btn-secondary {
            background-color: white;
            color: #1dd1a1;

            &:hover {
              background-color: #f8f9fa;
              transform: translateY(-2px);
              box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            }
          }

          &.btn-tertiary {
            background-color: #F5A623;
            color: white;

            &:hover {
              background-color: #D48817;
              transform: translateY(-2px);
              box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            }
          }
        }
      }
    }


  }

  /* Features Section */
  .features-section {
    padding: 4rem 0;
    background-color: #f8f9fa;

    .container-features {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 2rem;
    }

    .features-grid {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 2rem;

      @media (max-width: 1024px) {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
      }
    }

    .feature-card {
      text-align: left;
      padding: 1.5rem 0;

      .feature-icon {
        width: 40px;
        height: 40px;
        margin-bottom: 1rem;
        color: #00B4A6;
        font-size: 2rem;
        display: flex;
        align-items: center;
        justify-content: flex-start;
      }

      .feature-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 0.75rem;
        line-height: 1.3;
      }

      .feature-description {
        color: #666;
        line-height: 1.5;
        font-size: 0.95rem;
      }
    }
  }

  /* Professional Section */
  .professional-section {
    padding: 4rem 0;
    background: linear-gradient(135deg, #e8f5f3 0%, #d1f2ed 100%);

    .container-professional {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 2rem;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 3rem;
      align-items: center;

      @media (max-width: 768px) {
        display: flex;
        flex-direction: column;
        gap: 2rem;

      }
    }

    .professional-content {
      .professional-title {
        font-size: clamp(1.8rem, 4vw, 2.5rem);
        font-weight: 700;
        color: #333;
        margin-bottom: 1.5rem;
        line-height: 1.3;
      }

      .professional-list {
        list-style: none;
        padding: 0;
        margin-bottom: 2rem;

        li {
          color: #666;
          margin-bottom: 0.75rem;
          position: relative;
          padding-left: 1.5rem;
          line-height: 1.5;

          &::before {
            content: "•";
            color: #00B4A6;
            font-weight: bold;
            position: absolute;
            left: 0;
            font-size: 1.2rem;
          }
        }
      }

      .professional-cta {
        .btn-pro {
          background-color: #2B5CE6;
          color: white;
          padding: 0.8rem 1.5rem;
          border-radius: 6px;
          text-decoration: none;
          font-weight: 600;
          font-size: 1rem;
          transition: all 0.3s ease;
          display: inline-flex;
          align-items: center;
          gap: 0.5rem;
          box-shadow: 0 4px 15px rgba(43, 92, 230, 0.3);

          &:hover {
            background-color: #1e47c7;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(43, 92, 230, 0.4);
          }

          &::after {
            content: "→";
            font-size: 1.1rem;
          }
        }
      }
    }

    .professional-visual {
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;

      .mockup-container {
        position: relative;
        max-width: 500px;
        width: 100%;

        .doctor-image {
          width: 60%;
          border-radius: 12px;
          box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
          position: relative;
          z-index: 2;
        }

        .stat-card {
          position: absolute;
          background: white;
          border-radius: 12px;
          padding: 1rem;
          box-shadow: 0 6px 25px rgba(0, 0, 0, 0.1);

          &.income-card {
            top: 10%;
            left: -10%;
            z-index: 3;

            .stat-label {
              color: #666;
              font-size: 0.8rem;
              margin-bottom: 0.25rem;
            }

            .stat-value {
              color: #333;
              font-size: 1.2rem;
              font-weight: bold;
              display: flex;
              align-items: center;
              gap: 0.5rem;

              .growth {
                color: #00B4A6;
                font-size: 0.8rem;
              }
            }
          }

          &.appointment-card {
            bottom: 20%;
            left: -15%;
            z-index: 3;
            display: flex;
            align-items: center;
            gap: 0.75rem;

            .avatar {
              width: 40px;
              height: 40px;
              border-radius: 50%;
              background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
              display: flex;
              align-items: center;
              justify-content: center;
              color: white;
              font-weight: bold;
            }

            .appointment-info {
              .time {
                color: #666;
                font-size: 0.8rem;
              }

              .name {
                color: #333;
                font-weight: 600;
                font-size: 0.9rem;
              }
            }
          }

          &.rating-card {
            bottom: 15%;
            right: -10%;
            z-index: 3;

            .rating-stars {
              color: #FFD700;
              font-size: 1.2rem;
              margin-bottom: 0.25rem;
            }

            .rating-score {
              color: #333;
              font-weight: bold;
              font-size: 1.1rem;
            }

            .rating-label {
              color: #666;
              font-size: 0.7rem;
            }
          }
        }
      }
    }
  }

  /* Footer Section */
  .footer-section {
    background-color: #f8f9fa;
    padding: 3rem 0 1rem 0;

    .container-footer {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 2rem;
    }

    .footer-content {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 2rem;
      margin-bottom: 2rem;

      @media (max-width: 768px) {
        grid-template-columns: 1fr;
        gap: 1.5rem;
      }
    }

    .footer-column {
      .footer-title {
        font-size: 1rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 1rem;
      }

      .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;

        li {
          margin-bottom: 0.5rem;

          a {
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s ease;

            &:hover {
              color: #00B4A6;
            }
          }

          &.highlight a {
            color: #2B5CE6;
            font-weight: 600;
          }
        }
      }
    }

    .footer-brand {
      .brand-logo {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1rem;

        img {
          height: 32px;
          width: auto;
        }

        .brand-name {
          font-size: 1.5rem;
          font-weight: bold;
          color: #00B4A6;
        }
      }

      .brand-info {
        color: #666;
        font-size: 0.85rem;
        line-height: 1.4;
      }
    }

    .footer-bottom {
      border-top: 1px solid #e0e0e0;
      padding-top: 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1rem;

      @media (max-width: 768px) {
        flex-direction: column;
        text-align: center;
      }

      .footer-countries {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        font-size: 0.85rem;

        a {
          color: #2B5CE6;
          text-decoration: none;

          &:hover {
            text-decoration: underline;
          }
        }
      }

      .footer-copyright {
        color: #666;
        font-size: 0.85rem;
      }
    }
  }
</style>

<!-- Hero Section -->
<section class="hero-section">
  <div class="container-index">
    <!-- Content -->
    <div class="content">
      <h1 class="title">
        Encuentra tu especialista y agenda cita
      </h1>
      <p class="subtitle">
        profesionales están aquí para ayudarte.
      </p>

      <div class="cta-buttons">
        <?php if (!empty($_SESSION['user'])): ?>
          <a href="/dashboard" class="btn btn-primary">
            Dashboard
          </a>
        <?php else: ?>

          <a href="/login" class="btn btn-tertiary">
            Ingresar
          </a>
        <?php endif; ?>
      </div>
    </div>

  </div>
</section>

<!-- Features Section -->
<section class="features-section">
  <div class="container-features">
    <div class="features-grid">

      <!-- Feature 1 -->
      <div class="feature-card">
        <div class="feature-icon">🔍</div>
        <h3 class="feature-title">Encuentra tu especialista</h3>
        <p class="feature-description">
          Las opiniones reales de miles de pacientes te ayudarán a tomar siempre la mejor decisión.
        </p>
      </div>

      <!-- Feature 2 -->
      <div class="feature-card">
        <div class="feature-icon">📅</div>
        <h3 class="feature-title">Pide cita de forma fácil</h3>
        <p class="feature-description">
          Elige la hora que prefieras y pide cita sin necesidad de llamar. Es fácil, cómodo y muy rápido.
        </p>
      </div>

      <!-- Feature 3 -->

      <div class="feature-card">
        <div class="feature-icon">👍</div>
        <h3 class="feature-title">Sin costes añadidos</h3>
        <p class="feature-description">
          La reserva de cita es un servicio gratuito de Doctoralia.
        </p>
      </div>

    </div>
  </div>
</section>
<!-- Professional Section -->
<section class="professional-section">
  <div class="container-professional">

    <!-- Content -->
    <div class="professional-content">
      <h2 class="professional-title">
        ¿Eres profesional de la salud? Comienza a atraer nuevos pacientes
      </h2>

      <ul class="professional-list">
        <li>Conecta con pacientes que están buscando especialistas en tu localidad.</li>
        <li>Permite que los pacientes agenden contigo día y noche. Olvídate de esperar al horario de apertura.</li>
        <li>Mejora tu reputación en línea consiguiendo opiniones verificadas.</li>
      </ul>

      <div class="professional-cta">
        <a href="/register" class="btn-pro">Contactanos!</a>
      </div>
    </div>

    <!-- Visual Mockup -->
    <div class="professional-visual">
      <div class="mockup-container">

        <!-- Doctor Image -->
        <img src="/assets/img/divi-medical-theme-8.png" alt="Doctora Profesional" style="width: 60%; border-radius: 12px; box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1); position: relative; z-index: 2;" class="doctor-image" />

        <!-- Income Statistics Card -->
        <div class="stat-card income-card">
          <div class="stat-label">Ingresos</div>
          <div class="stat-value">
            S/ 200
            <span class="growth">↑ 54,8%</span>
          </div>
        </div>

        <!-- Appointment Card -->
        <div class="stat-card appointment-card">
          <div class="avatar">RM</div>
          <div class="appointment-info">
            <div class="time">10:00</div>
            <div class="name">Rubén Morales</div>
          </div>
        </div>

        <!-- Rating Card -->
        <div class="stat-card rating-card">
          <div class="rating-stars">⭐⭐⭐⭐⭐</div>
          <div class="rating-score">4.8</div>
          <div class="rating-label">Valoración global<br>28 opiniones</div>
        </div>

      </div>
    </div>

  </div>
</section>
<!-- Footer Section -->
<footer class="footer-section">
  <div class="container-footer">

    <!-- Footer Content -->
    <div class="footer-content">

      <!-- Servicio Column -->
      <div class="footer-column">
    
      </div>

      <!-- Para los pacientes Column -->
      <div class="footer-column">
 
      </div>

      <!-- Para profesionales Column -->
      <div class="footer-column">
 
      </div>

      <!-- Brand Column -->
      <div class="footer-column footer-brand">
        <div class="brand-logo">
          <img src="/assets/img/doctoralia.png" alt="Doctoralia">
           
        </div>
        <div class="brand-info">
          Doctoralia Internet SL<br>
          C/ Josep Pla 2 - Building B2, floor 13<br>
          08019 Barcelona, Spain
        </div>
      </div>

    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
      <div class="footer-countries">
        <a href="#">Perú</a>
      </div>
      <div class="footer-copyright">
        www.doctoralia.pe © 2025 - Encuentra tu especialista y agenda cita
      </div>
    </div>

  </div>
</footer>