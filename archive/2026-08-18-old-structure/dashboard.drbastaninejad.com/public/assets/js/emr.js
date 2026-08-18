// EMR module — clinical note drawer, launched from Patient Timeline or Calendar
const EmrModule = (() => {
  let currentPatientId = null;
  let currentAppointmentId = null;
  let aiDraftText = '';

  function open(patientId, appointmentId = null) {
    currentPatientId = patientId;
    currentAppointmentId = appointmentId;
    document.getElementById('emr-chief-complaint').value = '';
    document.getElementById('emr-diagnosis').value = '';
    document.getElementById('emr-plan').value = '';
    document.getElementById('emr-ai-draft').style.display = 'none';
    document.getElementById('emr-ai-actions').style.display = 'none';
    aiDraftText = '';
    document.getElementById('emr-drawer').style.display = 'block';
  }

  function close() {
    document.getElementById('emr-drawer').style.display = 'none';
  }

  async function requestAiDraft() {
    const chief = document.getElementById('emr-chief-complaint').value.trim();
    if (!chief) { alert('ابتدا شرح شکایت اصلی را وارد کنید'); return; }
    const btn = document.getElementById('emr-ai-suggest-btn');
    btn.disabled = true;
    btn.textContent = 'در حال دریافت...';
    try {
      const res = await api('/ai/emr-draft', { method: 'POST', body: JSON.stringify({ chief_complaint: chief }) });
      aiDraftText = res.data.draft;
      const draftEl = document.getElementById('emr-ai-draft');
      draftEl.textContent = aiDraftText;
      draftEl.style.display = 'block';
      document.getElementById('emr-ai-actions').style.display = 'flex';
    } catch (e) {
      alert('خطا در دریافت پیشنهاد هوش مصنوعی');
      console.warn(e.message);
    } finally {
      btn.disabled = false;
      btn.textContent = 'پیشنهاد بگیر';
    }
  }

  function acceptAiDraft() {
    const planEl = document.getElementById('emr-plan');
    planEl.value = (planEl.value ? planEl.value + '\n' : '') + aiDraftText;
    document.getElementById('emr-ai-draft').style.display = 'none';
    document.getElementById('emr-ai-actions').style.display = 'none';
  }

  function discardAiDraft() {
    document.getElementById('emr-ai-draft').style.display = 'none';
    document.getElementById('emr-ai-actions').style.display = 'none';
    aiDraftText = '';
  }

  async function save() {
    const chief = document.getElementById('emr-chief-complaint').value.trim();
    if (!chief) { alert('شرح شکایت اصلی الزامی است'); return; }

    const payload = {
      chief_complaint: chief,
      diagnosis: document.getElementById('emr-diagnosis').value.trim() || null,
      plan: document.getElementById('emr-plan').value.trim() || null,
      appointment_id: currentAppointmentId,
    };

    try {
      await api(`/patients/${currentPatientId}/emr`, { method: 'POST', body: JSON.stringify(payload) });
      close();
      if (typeof PatientsModule !== 'undefined' && document.getElementById('view-patient-detail').style.display === 'block') {
        PatientsModule.load();
      }
    } catch (e) {
      alert('خطا در ذخیره یادداشت بالینی');
      console.warn(e.message);
    }
  }

  function init() {
    document.getElementById('emr-close-btn').addEventListener('click', close);
    document.getElementById('emr-cancel-btn').addEventListener('click', close);
    document.getElementById('emr-save-btn').addEventListener('click', save);
    document.getElementById('emr-ai-suggest-btn').addEventListener('click', requestAiDraft);
    document.getElementById('emr-ai-accept').addEventListener('click', acceptAiDraft);
    document.getElementById('emr-ai-discard').addEventListener('click', discardAiDraft);
  }

  return { init, open, close };
})();
