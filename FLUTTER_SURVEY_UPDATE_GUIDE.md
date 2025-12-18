# 📱 Flutter Survey Update Guide

## Overview
Panduan untuk mengupdate Flutter app agar menggunakan sistem survey dinamis dari API, menggantikan hardcoded questions.

---

## 🔧 Files yang Perlu Dimodifikasi/Dibuat

### 1. **lib/core/models/survey_models.dart** (BARU)
Model untuk API response dari endpoint `/api/survey/questions`

```dart
class SurveyTemplateResponse {
  final bool success;
  final SurveyTemplateData? data;

  SurveyTemplateResponse({
    required this.success,
    this.data,
  });

  factory SurveyTemplateResponse.fromJson(Map<String, dynamic> json) {
    return SurveyTemplateResponse(
      success: json['success'] ?? false,
      data: json['data'] != null
          ? SurveyTemplateData.fromJson(json['data'])
          : null,
    );
  }
}

class SurveyTemplateData {
  final SurveyTemplateInfo template;
  final List<SurveyQuestion> questions;

  SurveyTemplateData({
    required this.template,
    required this.questions,
  });

  factory SurveyTemplateData.fromJson(Map<String, dynamic> json) {
    return SurveyTemplateData(
      template: SurveyTemplateInfo.fromJson(json['template']),
      questions: (json['questions'] as List)
          .map((q) => SurveyQuestion.fromJson(q))
          .toList(),
    );
  }
}

class SurveyTemplateInfo {
  final int id;
  final String nama;
  final int versi;
  final String? deskripsi;

  SurveyTemplateInfo({
    required this.id,
    required this.nama,
    required this.versi,
    this.deskripsi,
  });

  factory SurveyTemplateInfo.fromJson(Map<String, dynamic> json) {
    return SurveyTemplateInfo(
      id: json['id'],
      nama: json['nama'],
      versi: json['versi'],
      deskripsi: json['deskripsi'],
    );
  }
}

class SurveyQuestion {
  final int id;
  final String pertanyaan;
  final String? kodeUnsur;
  final int urutan;
  final bool isRequired;
  final bool isTextInput;
  final List<SurveyOption> options;

  SurveyQuestion({
    required this.id,
    required this.pertanyaan,
    this.kodeUnsur,
    required this.urutan,
    required this.isRequired,
    required this.isTextInput,
    required this.options,
  });

  factory SurveyQuestion.fromJson(Map<String, dynamic> json) {
    return SurveyQuestion(
      id: json['id'],
      pertanyaan: json['pertanyaan'],
      kodeUnsur: json['kode_unsur'],
      urutan: json['urutan'],
      isRequired: json['is_required'] ?? true,
      isTextInput: json['is_text_input'] ?? false,
      options: json['is_text_input'] == true
          ? []
          : (json['options'] as List)
              .map((o) => SurveyOption.fromJson(o))
              .toList(),
    );
  }
}

class SurveyOption {
  final int id;
  final String jawaban;
  final int poin;
  final int urutan;

  SurveyOption({
    required this.id,
    required this.jawaban,
    required this.poin,
    required this.urutan,
  });

  factory SurveyOption.fromJson(Map<String, dynamic> json) {
    return SurveyOption(
      id: json['id'],
      jawaban: json['jawaban'],
      poin: json['poin'],
      urutan: json['urutan'],
    );
  }
}

class SurveySubmissionRequest {
  final int surveyTemplateId;
  final int? antrianId;
  final String? nomorAntrian;
  final int? layananPublikId;
  final String namaResponden;
  final String? noHpWa;
  final int usia;
  final String jenisKelamin;
  final String pendidikan;
  final String pekerjaan;
  final String? bidang;
  final String? tanggal;
  final List<SurveyResponseItem> responses;
  final String? saran;

  SurveySubmissionRequest({
    required this.surveyTemplateId,
    this.antrianId,
    this.nomorAntrian,
    this.layananPublikId,
    required this.namaResponden,
    this.noHpWa,
    required this.usia,
    required this.jenisKelamin,
    required this.pendidikan,
    required this.pekerjaan,
    this.bidang,
    this.tanggal,
    required this.responses,
    this.saran,
  });

  Map<String, dynamic> toJson() {
    return {
      'survey_template_id': surveyTemplateId,
      'antrian_id': antrianId,
      'nomor_antrian': nomorAntrian,
      'layanan_publik_id': layananPublikId,
      'nama_responden': namaResponden,
      'no_hp_wa': noHpWa,
      'usia': usia,
      'jenis_kelamin': jenisKelamin,
      'pendidikan': pendidikan,
      'pekerjaan': pekerjaan,
      'bidang': bidang,
      'tanggal': tanggal,
      'responses': responses.map((r) => r.toJson()).toList(),
      'saran': saran,
    };
  }
}

class SurveyResponseItem {
  final int questionId;
  final int? optionId;
  final String? textAnswer;
  final int? poin;

  SurveyResponseItem({
    required this.questionId,
    this.optionId,
    this.textAnswer,
    this.poin,
  });

  Map<String, dynamic> toJson() {
    return {
      'question_id': questionId,
      'option_id': optionId,
      'text_answer': textAnswer,
      'poin': poin,
    };
  }
}
```

---

### 2. **lib/core/services/survey_service.dart** (UPDATE)

Tambahkan method untuk fetch questions:

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/survey_models.dart';

class SurveyService {
  final String baseUrl;

  SurveyService({required this.baseUrl});

  /// Fetch survey questions dari template aktif
  Future<SurveyTemplateResponse> fetchSurveyQuestions({int? templateId}) async {
    try {
      final uri = Uri.parse('$baseUrl/api/survey/questions')
          .replace(queryParameters: templateId != null
              ? {'template_id': templateId.toString()}
              : null);

      final response = await http.get(
        uri,
        headers: {'Accept': 'application/json'},
      );

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        return SurveyTemplateResponse.fromJson(jsonData);
      } else if (response.statusCode == 404) {
        return SurveyTemplateResponse(
          success: false,
          data: null,
        );
      } else {
        throw Exception('Failed to load survey questions: ${response.statusCode}');
      }
    } catch (e) {
      throw Exception('Error fetching survey questions: $e');
    }
  }

  /// Submit survey dengan format baru (template-based)
  Future<Map<String, dynamic>> submitSurvey(SurveySubmissionRequest request) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/api/survey'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: json.encode(request.toJson()),
      );

      final responseData = json.decode(response.body);

      if (response.statusCode == 201 || response.statusCode == 200) {
        return {
          'success': true,
          'message': responseData['message'] ?? 'Survey berhasil disimpan',
          'data': responseData['data'],
        };
      } else if (response.statusCode == 422) {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Survey sudah pernah diisi',
          'data': responseData['data'],
        };
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Gagal menyimpan survey',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Error: $e',
      };
    }
  }
}
```

---

### 3. **lib/features/home/survey_page.dart** (UPDATE)

Ubah dari hardcoded questions menjadi fetch dari API:

```dart
import 'package:flutter/material.dart';
import '../../core/services/survey_service.dart';
import '../../core/models/survey_models.dart';
import 'widgets/survey/question_step.dart';

class SurveyPage extends StatefulWidget {
  final String? nomorAntrian;
  final int? layananPublikId;

  const SurveyPage({
    Key? key,
    this.nomorAntrian,
    this.layananPublikId,
  }) : super(key: key);

  @override
  State<SurveyPage> createState() => _SurveyPageState();
}

class _SurveyPageState extends State<SurveyPage> {
  final SurveyService _surveyService = SurveyService(
    baseUrl: 'YOUR_BASE_URL', // Ganti dengan base URL Anda
  );

  SurveyTemplateData? _surveyData;
  bool _isLoading = true;
  String? _errorMessage;

  int _currentStep = 0;
  Map<int, dynamic> _answers = {}; // questionId => {optionId, poin} atau textAnswer

  @override
  void initState() {
    super.initState();
    _loadSurveyQuestions();
  }

  Future<void> _loadSurveyQuestions() async {
    try {
      setState(() {
        _isLoading = true;
        _errorMessage = null;
      });

      final response = await _surveyService.fetchSurveyQuestions();

      if (response.success && response.data != null) {
        setState(() {
          _surveyData = response.data;
          _isLoading = false;
        });
      } else {
        setState(() {
          _errorMessage = 'Tidak ada template survey aktif saat ini.';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'Gagal memuat pertanyaan survey: $e';
        _isLoading = false;
      });
    }
  }

  void _onAnswerChanged(int questionId, dynamic answer) {
    setState(() {
      _answers[questionId] = answer;
    });
  }

  bool _canProceed() {
    if (_surveyData == null) return false;

    final currentQuestion = _surveyData!.questions[_currentStep];

    if (!currentQuestion.isRequired) return true;

    return _answers.containsKey(currentQuestion.id) &&
           _answers[currentQuestion.id] != null;
  }

  void _nextStep() {
    if (_currentStep < _surveyData!.questions.length - 1) {
      setState(() {
        _currentStep++;
      });
    } else {
      _submitSurvey();
    }
  }

  void _previousStep() {
    if (_currentStep > 0) {
      setState(() {
        _currentStep--;
      });
    }
  }

  Future<void> _submitSurvey() async {
    // Tampilkan loading dialog
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => const Center(child: CircularProgressIndicator()),
    );

    try {
      // Build responses array
      final responses = _answers.entries.map((entry) {
        final questionId = entry.key;
        final answer = entry.value;

        if (answer is String) {
          // Text input
          return SurveyResponseItem(
            questionId: questionId,
            textAnswer: answer,
          );
        } else if (answer is Map) {
          // Multiple choice
          return SurveyResponseItem(
            questionId: questionId,
            optionId: answer['optionId'],
            poin: answer['poin'],
          );
        }
        return null;
      }).whereType<SurveyResponseItem>().toList();

      final request = SurveySubmissionRequest(
        surveyTemplateId: _surveyData!.template.id,
        nomorAntrian: widget.nomorAntrian,
        layananPublikId: widget.layananPublikId,
        namaResponden: 'Nama Responden', // Ambil dari form
        usia: 30, // Ambil dari form
        jenisKelamin: 'Laki-laki', // Ambil dari form
        pendidikan: 'S1', // Ambil dari form
        pekerjaan: 'PNS', // Ambil dari form
        responses: responses,
      );

      final result = await _surveyService.submitSurvey(request);

      // Tutup loading dialog
      Navigator.pop(context);

      if (result['success']) {
        // Tampilkan success dialog
        _showSuccessDialog(result['message']);
      } else {
        // Tampilkan error dialog
        _showErrorDialog(result['message']);
      }
    } catch (e) {
      Navigator.pop(context);
      _showErrorDialog('Gagal mengirim survey: $e');
    }
  }

  void _showSuccessDialog(String message) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Berhasil'),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.pop(context); // Tutup dialog
              Navigator.pop(context); // Kembali ke halaman sebelumnya
            },
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }

  void _showErrorDialog(String message) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Error'),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Survey Kepuasan'),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _errorMessage != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(_errorMessage!),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _loadSurveyQuestions,
                        child: const Text('Coba Lagi'),
                      ),
                    ],
                  ),
                )
              : _surveyData != null
                  ? Column(
                      children: [
                        LinearProgressIndicator(
                          value: (_currentStep + 1) / _surveyData!.questions.length,
                        ),
                        Expanded(
                          child: QuestionStep(
                            question: _surveyData!.questions[_currentStep],
                            currentAnswer: _answers[_surveyData!.questions[_currentStep].id],
                            onAnswerChanged: (answer) {
                              _onAnswerChanged(
                                _surveyData!.questions[_currentStep].id,
                                answer,
                              );
                            },
                          ),
                        ),
                        Padding(
                          padding: const EdgeInsets.all(16.0),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              if (_currentStep > 0)
                                ElevatedButton(
                                  onPressed: _previousStep,
                                  child: const Text('Sebelumnya'),
                                ),
                              const Spacer(),
                              ElevatedButton(
                                onPressed: _canProceed() ? _nextStep : null,
                                child: Text(
                                  _currentStep == _surveyData!.questions.length - 1
                                      ? 'Kirim'
                                      : 'Selanjutnya',
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    )
                  : const Center(child: Text('Tidak ada data survey')),
    );
  }
}
```

---

### 4. **lib/features/home/widgets/survey/question_step.dart** (UPDATE)

Widget untuk render pertanyaan secara dinamis:

```dart
import 'package:flutter/material.dart';
import '../../../../core/models/survey_models.dart';

class QuestionStep extends StatelessWidget {
  final SurveyQuestion question;
  final dynamic currentAnswer;
  final Function(dynamic) onAnswerChanged;

  const QuestionStep({
    Key? key,
    required this.question,
    this.currentAnswer,
    required this.onAnswerChanged,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Pertanyaan ${question.urutan}',
            style: Theme.of(context).textTheme.bodySmall,
          ),
          const SizedBox(height: 8),
          Text(
            question.pertanyaan,
            style: Theme.of(context).textTheme.titleLarge,
          ),
          const SizedBox(height: 24),

          // Render berdasarkan tipe pertanyaan
          if (question.isTextInput)
            _buildTextInput()
          else
            _buildMultipleChoice(),
        ],
      ),
    );
  }

  Widget _buildTextInput() {
    return TextField(
      decoration: InputDecoration(
        hintText: 'Masukkan jawaban Anda...',
        border: const OutlineInputBorder(),
        suffixIcon: question.isRequired
            ? const Icon(Icons.star, color: Colors.red, size: 12)
            : null,
      ),
      maxLines: 5,
      onChanged: (value) => onAnswerChanged(value),
      controller: TextEditingController(text: currentAnswer ?? ''),
    );
  }

  Widget _buildMultipleChoice() {
    return Column(
      children: question.options.map((option) {
        final isSelected = currentAnswer != null &&
                          currentAnswer['optionId'] == option.id;

        return Card(
          margin: const EdgeInsets.only(bottom: 12),
          color: isSelected ? Colors.blue.shade50 : null,
          child: ListTile(
            title: Text(option.jawaban),
            trailing: isSelected
                ? const Icon(Icons.check_circle, color: Colors.blue)
                : null,
            onTap: () {
              onAnswerChanged({
                'optionId': option.id,
                'poin': option.poin,
              });
            },
          ),
        );
      }).toList(),
    );
  }
}
```

---

## 🔄 Migration Path

### Backward Compatibility
Sistem backend sudah mendukung **dual format**, jadi app lama tetap bisa berjalan:
- App versi lama → submit dengan format lama (jawaban JSON)
- App versi baru → submit dengan format baru (survey_template_id + responses)

### Deployment Steps
1. Deploy backend dengan dual format support
2. Test API endpoint `/api/survey/questions`
3. Update Flutter app dengan code di atas
4. Test submission dengan format baru
5. Deploy Flutter app update ke production

---

## 📝 Catatan Penting

1. **Base URL**: Ganti `YOUR_BASE_URL` dengan URL backend yang sebenarnya
2. **Form Data**: Contoh di atas menggunakan hardcoded data responden, sesuaikan dengan form input yang ada
3. **Error Handling**: Tambahkan error handling yang lebih robust sesuai kebutuhan
4. **State Management**: Jika menggunakan Provider/Bloc, sesuaikan dengan pattern yang ada
5. **Loading States**: Tambahkan shimmer/skeleton loading untuk UX yang lebih baik

---

## ✅ Testing Checklist

- [ ] API `/api/survey/questions` mengembalikan data yang benar
- [ ] Loading state ditampilkan saat fetch questions
- [ ] Error handling bekerja jika API gagal
- [ ] Pertanyaan ditampilkan sesuai urutan
- [ ] Pilihan jawaban ditampilkan dengan benar
- [ ] Text input berfungsi untuk pertanyaan terbuka
- [ ] Validasi required questions bekerja
- [ ] Navigation (next/previous) berfungsi
- [ ] Submit survey berhasil dengan format baru
- [ ] Success/error message ditampilkan
- [ ] Duplicate submission dicegah (jika ada layanan_publik_id)
