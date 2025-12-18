# ⚛️ React Web Survey Update Guide

## Overview
Panduan untuk mengupdate React web app (gaspul-web) agar menggunakan sistem survey dinamis dari API, menggantikan hardcoded questions.

---

## 🔧 File yang Perlu Dimodifikasi

### **src/components/ui/ModalSurvey.tsx** (UPDATE)

Ubah dari hardcoded questions menjadi fetch dari API:

```typescript
import React, { useState, useEffect } from 'react';
import axios from 'axios';

// ==================== INTERFACES ====================

interface SurveyTemplate {
  id: number;
  nama: string;
  versi: number;
  deskripsi: string | null;
}

interface SurveyOption {
  id: number;
  jawaban: string;
  poin: number;
  urutan: number;
}

interface SurveyQuestion {
  id: number;
  pertanyaan: string;
  kode_unsur: string | null;
  urutan: number;
  is_required: boolean;
  is_text_input: boolean;
  options: SurveyOption[];
}

interface SurveyTemplateData {
  template: SurveyTemplate;
  questions: SurveyQuestion[];
}

interface SurveyResponse {
  question_id: number;
  option_id?: number;
  text_answer?: string;
  poin?: number;
}

interface ModalSurveyProps {
  isOpen: boolean;
  onClose: () => void;
  nomorAntrian?: string;
  layananPublikId?: number;
}

// ==================== COMPONENT ====================

const ModalSurvey: React.FC<ModalSurveyProps> = ({
  isOpen,
  onClose,
  nomorAntrian,
  layananPublikId,
}) => {
  // State untuk survey data dari API
  const [surveyData, setSurveyData] = useState<SurveyTemplateData | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);

  // State untuk navigasi dan jawaban
  const [currentStep, setCurrentStep] = useState<number>(0);
  const [answers, setAnswers] = useState<Map<number, any>>(new Map());

  // State untuk form data responden
  const [formData, setFormData] = useState({
    namaResponden: '',
    noHpWa: '',
    usia: '',
    jenisKelamin: 'Laki-laki',
    pendidikan: 'SD',
    pekerjaan: 'PNS',
    bidang: '',
    saran: '',
  });

  const [isSubmitting, setIsSubmitting] = useState<boolean>(false);

  // Base URL API (sesuaikan dengan environment)
  const API_BASE_URL = process.env.REACT_APP_API_BASE_URL || 'http://localhost:8000';

  // ==================== FETCH SURVEY QUESTIONS ====================

  useEffect(() => {
    if (isOpen) {
      fetchSurveyQuestions();
    }
  }, [isOpen]);

  const fetchSurveyQuestions = async () => {
    try {
      setIsLoading(true);
      setErrorMessage(null);

      const response = await axios.get(`${API_BASE_URL}/api/survey/questions`, {
        headers: {
          'Accept': 'application/json',
        },
      });

      if (response.data.success && response.data.data) {
        setSurveyData(response.data.data);
      } else {
        setErrorMessage('Tidak ada template survey aktif saat ini.');
      }
    } catch (error: any) {
      console.error('Error fetching survey questions:', error);
      setErrorMessage(
        error.response?.data?.message ||
        'Gagal memuat pertanyaan survey. Silakan coba lagi.'
      );
    } finally {
      setIsLoading(false);
    }
  };

  // ==================== ANSWER HANDLERS ====================

  const handleAnswerChange = (questionId: number, answer: any) => {
    setAnswers(prev => {
      const newAnswers = new Map(prev);
      newAnswers.set(questionId, answer);
      return newAnswers;
    });
  };

  const canProceed = (): boolean => {
    if (!surveyData) return false;

    const currentQuestion = surveyData.questions[currentStep];

    if (!currentQuestion.is_required) return true;

    const answer = answers.get(currentQuestion.id);

    if (currentQuestion.is_text_input) {
      return answer && answer.trim().length > 0;
    } else {
      return answer !== undefined && answer !== null;
    }
  };

  // ==================== NAVIGATION ====================

  const handleNext = () => {
    if (currentStep < surveyData!.questions.length - 1) {
      setCurrentStep(prev => prev + 1);
    } else {
      handleSubmit();
    }
  };

  const handlePrevious = () => {
    if (currentStep > 0) {
      setCurrentStep(prev => prev - 1);
    }
  };

  // ==================== SUBMIT SURVEY ====================

  const handleSubmit = async () => {
    if (!surveyData) return;

    setIsSubmitting(true);

    try {
      // Build responses array
      const responses: SurveyResponse[] = [];

      answers.forEach((answer, questionId) => {
        const question = surveyData.questions.find(q => q.id === questionId);

        if (!question) return;

        if (question.is_text_input) {
          // Text input response
          responses.push({
            question_id: questionId,
            text_answer: answer as string,
          });
        } else {
          // Multiple choice response
          responses.push({
            question_id: questionId,
            option_id: answer.optionId,
            poin: answer.poin,
          });
        }
      });

      // Prepare submission payload
      const payload = {
        survey_template_id: surveyData.template.id,
        nomor_antrian: nomorAntrian,
        layanan_publik_id: layananPublikId,
        nama_responden: formData.namaResponden,
        no_hp_wa: formData.noHpWa,
        usia: parseInt(formData.usia),
        jenis_kelamin: formData.jenisKelamin,
        pendidikan: formData.pendidikan,
        pekerjaan: formData.pekerjaan,
        bidang: formData.bidang,
        tanggal: new Date().toISOString().split('T')[0],
        responses: responses,
        saran: formData.saran,
      };

      // Submit to API
      const response = await axios.post(`${API_BASE_URL}/api/survey`, payload, {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      });

      if (response.data.success) {
        alert('Survey berhasil disimpan! Terima kasih atas partisipasi Anda.');
        handleClose();
      } else {
        alert(response.data.message || 'Gagal menyimpan survey.');
      }
    } catch (error: any) {
      console.error('Error submitting survey:', error);

      if (error.response?.status === 422) {
        alert(error.response.data.message || 'Survey untuk layanan ini sudah pernah diisi.');
      } else {
        alert('Terjadi kesalahan saat mengirim survey. Silakan coba lagi.');
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  // ==================== CLOSE HANDLER ====================

  const handleClose = () => {
    setCurrentStep(0);
    setAnswers(new Map());
    setFormData({
      namaResponden: '',
      noHpWa: '',
      usia: '',
      jenisKelamin: 'Laki-laki',
      pendidikan: 'SD',
      pekerjaan: 'PNS',
      bidang: '',
      saran: '',
    });
    onClose();
  };

  // ==================== RENDER ====================

  if (!isOpen) return null;

  return (
    <div className="modal-overlay" onClick={handleClose}>
      <div className="modal-content" onClick={e => e.stopPropagation()}>
        <div className="modal-header">
          <h2>Survey Kepuasan Masyarakat</h2>
          <button className="close-button" onClick={handleClose}>
            &times;
          </button>
        </div>

        <div className="modal-body">
          {isLoading ? (
            <div className="loading-container">
              <div className="spinner"></div>
              <p>Memuat pertanyaan survey...</p>
            </div>
          ) : errorMessage ? (
            <div className="error-container">
              <p className="error-message">{errorMessage}</p>
              <button onClick={fetchSurveyQuestions} className="btn-retry">
                Coba Lagi
              </button>
            </div>
          ) : surveyData ? (
            <>
              {/* Progress Bar */}
              <div className="progress-bar">
                <div
                  className="progress-fill"
                  style={{
                    width: `${((currentStep + 1) / surveyData.questions.length) * 100}%`,
                  }}
                />
              </div>

              <div className="question-counter">
                Pertanyaan {currentStep + 1} dari {surveyData.questions.length}
              </div>

              {/* Current Question */}
              <QuestionRenderer
                question={surveyData.questions[currentStep]}
                currentAnswer={answers.get(surveyData.questions[currentStep].id)}
                onAnswerChange={(answer) =>
                  handleAnswerChange(surveyData.questions[currentStep].id, answer)
                }
              />

              {/* Navigation Buttons */}
              <div className="modal-footer">
                <button
                  className="btn-secondary"
                  onClick={handlePrevious}
                  disabled={currentStep === 0}
                >
                  Sebelumnya
                </button>

                <button
                  className="btn-primary"
                  onClick={handleNext}
                  disabled={!canProceed() || isSubmitting}
                >
                  {isSubmitting
                    ? 'Mengirim...'
                    : currentStep === surveyData.questions.length - 1
                    ? 'Kirim Survey'
                    : 'Selanjutnya'}
                </button>
              </div>
            </>
          ) : (
            <div className="error-container">
              <p>Tidak ada data survey.</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

// ==================== QUESTION RENDERER COMPONENT ====================

interface QuestionRendererProps {
  question: SurveyQuestion;
  currentAnswer: any;
  onAnswerChange: (answer: any) => void;
}

const QuestionRenderer: React.FC<QuestionRendererProps> = ({
  question,
  currentAnswer,
  onAnswerChange,
}) => {
  if (question.is_text_input) {
    // Text Input Question
    return (
      <div className="question-container">
        <h3 className="question-text">
          {question.pertanyaan}
          {question.is_required && <span className="required">*</span>}
        </h3>

        <textarea
          className="text-input"
          placeholder="Masukkan jawaban Anda..."
          value={currentAnswer || ''}
          onChange={(e) => onAnswerChange(e.target.value)}
          rows={5}
        />
      </div>
    );
  }

  // Multiple Choice Question
  return (
    <div className="question-container">
      <h3 className="question-text">
        {question.pertanyaan}
        {question.is_required && <span className="required">*</span>}
      </h3>

      <div className="options-container">
        {question.options
          .sort((a, b) => a.urutan - b.urutan)
          .map((option) => {
            const isSelected =
              currentAnswer && currentAnswer.optionId === option.id;

            return (
              <div
                key={option.id}
                className={`option-card ${isSelected ? 'selected' : ''}`}
                onClick={() =>
                  onAnswerChange({
                    optionId: option.id,
                    poin: option.poin,
                  })
                }
              >
                <span className="option-text">{option.jawaban}</span>
                {isSelected && <span className="check-icon">✓</span>}
              </div>
            );
          })}
      </div>
    </div>
  );
};

export default ModalSurvey;
```

---

## 🎨 CSS Styles (Optional - jika belum ada)

Tambahkan CSS berikut ke file styles Anda:

```css
/* Modal Overlay */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

/* Modal Content */
.modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Modal Header */
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
  color: #111827;
}

.close-button {
  background: none;
  border: none;
  font-size: 2rem;
  color: #6b7280;
  cursor: pointer;
  line-height: 1;
}

.close-button:hover {
  color: #111827;
}

/* Modal Body */
.modal-body {
  padding: 20px;
}

/* Loading State */
.loading-container {
  text-align: center;
  padding: 40px 20px;
}

.spinner {
  border: 4px solid #f3f4f6;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Error State */
.error-container {
  text-align: center;
  padding: 40px 20px;
}

.error-message {
  color: #dc2626;
  margin-bottom: 16px;
}

.btn-retry {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 1rem;
}

.btn-retry:hover {
  background: #2563eb;
}

/* Progress Bar */
.progress-bar {
  width: 100%;
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 16px;
}

.progress-fill {
  height: 100%;
  background: #3b82f6;
  transition: width 0.3s ease;
}

/* Question Counter */
.question-counter {
  text-align: center;
  color: #6b7280;
  font-size: 0.875rem;
  margin-bottom: 24px;
}

/* Question Container */
.question-container {
  margin-bottom: 24px;
}

.question-text {
  font-size: 1.25rem;
  color: #111827;
  margin-bottom: 16px;
  line-height: 1.6;
}

.required {
  color: #dc2626;
  margin-left: 4px;
}

/* Text Input */
.text-input {
  width: 100%;
  padding: 12px;
  border: 2px solid #e5e7eb;
  border-radius: 6px;
  font-size: 1rem;
  font-family: inherit;
  resize: vertical;
}

.text-input:focus {
  outline: none;
  border-color: #3b82f6;
}

/* Options Container */
.options-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Option Card */
.option-card {
  padding: 16px;
  border: 2px solid #e5e7eb;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.option-card:hover {
  border-color: #3b82f6;
  background: #eff6ff;
}

.option-card.selected {
  border-color: #3b82f6;
  background: #dbeafe;
}

.option-text {
  font-size: 1rem;
  color: #111827;
}

.check-icon {
  color: #3b82f6;
  font-weight: bold;
  font-size: 1.25rem;
}

/* Modal Footer */
.modal-footer {
  display: flex;
  justify-content: space-between;
  padding: 20px;
  border-top: 1px solid #e5e7eb;
}

/* Buttons */
.btn-primary, .btn-secondary {
  padding: 10px 24px;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
}

.btn-primary:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
}

.btn-secondary:hover:not(:disabled) {
  background: #e5e7eb;
}

.btn-secondary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
```

---

## 🔄 Environment Variables

Tambahkan ke file `.env` di root project React:

```env
REACT_APP_API_BASE_URL=http://localhost:8000
```

Untuk production:
```env
REACT_APP_API_BASE_URL=https://your-production-domain.com
```

---

## 📦 Dependencies

Pastikan package berikut sudah terinstall:

```bash
npm install axios
# atau
yarn add axios
```

---

## 🔄 Migration Path

### Backward Compatibility
Backend sudah mendukung **dual format**:
- App versi lama → format lama (jawaban JSON)
- App versi baru → format baru (survey_template_id + responses)

### Deployment Steps
1. Deploy backend dengan dual format support ✅ (sudah selesai)
2. Test API endpoint `/api/survey/questions`
3. Update React component dengan code di atas
4. Test submission dengan format baru
5. Deploy React app update ke production

---

## ✅ Testing Checklist

- [ ] API `/api/survey/questions` mengembalikan data yang benar
- [ ] Loading state ditampilkan saat fetch questions
- [ ] Error handling bekerja jika API gagal atau tidak ada template aktif
- [ ] Pertanyaan ditampilkan sesuai urutan
- [ ] Pilihan jawaban ditampilkan dengan benar
- [ ] Text input berfungsi untuk pertanyaan terbuka
- [ ] Validasi required questions bekerja
- [ ] Progress bar update sesuai step
- [ ] Navigation (previous/next) berfungsi
- [ ] Submit survey berhasil dengan format baru
- [ ] Success/error message ditampilkan dengan benar
- [ ] Duplicate submission dicegah (jika ada layanan_publik_id)
- [ ] Modal close & reset state dengan benar

---

## 📝 Catatan Penting

1. **Environment Variables**: Jangan lupa set `REACT_APP_API_BASE_URL`
2. **Form Data**: Contoh di atas belum include form input untuk data responden. Sesuaikan dengan UI existing
3. **TypeScript**: Pastikan interfaces sesuai dengan response API
4. **Error Handling**: Tambahkan toast notification untuk UX lebih baik
5. **Styling**: Sesuaikan CSS dengan design system yang ada
6. **State Management**: Jika menggunakan Redux/Context, sesuaikan dengan pattern yang ada

---

## 🐛 Common Issues

### Issue 1: CORS Error
**Solution**: Pastikan backend Laravel sudah enable CORS untuk domain React app

### Issue 2: 404 Not Found
**Solution**: Periksa `REACT_APP_API_BASE_URL` dan pastikan routes sudah registered di backend

### Issue 3: Validation Error 422
**Solution**: Periksa format request body sesuai dengan validation rules di backend

---

## 🎯 Next Steps

Setelah implementasi React selesai:
1. Test integrasi end-to-end
2. Deploy ke staging environment
3. UAT dengan user
4. Deploy ke production
5. Monitor error logs
6. Train admin untuk gunakan panel baru
