"use client";

import { FormEvent, useMemo, useState } from "react";
import { services } from "../site-data";
import { Icon } from "./icons";

type AppointmentData = {
  name: string;
  phone: string;
  age: string;
  service: string;
  history: string;
  day: string;
  time: string;
  notes: string;
};

const initialData: AppointmentData = {
  name: "",
  phone: "",
  age: "",
  service: "",
  history: "",
  day: "",
  time: "",
  notes: "",
};

const days = [
  { id: "sat-next", label: "شنبه", detail: "نزدیک‌ترین روز آزاد" },
  { id: "tue-next", label: "سه‌شنبه", detail: "نوبت عصر" },
  { id: "sat-following", label: "شنبه بعد", detail: "ظرفیت مناسب" },
];

const times = ["۱۵:۰۰", "۱۵:۴۵", "۱۶:۳۰", "۱۷:۱۵", "۱۸:۰۰", "۱۸:۴۵"];

export function AppointmentForm() {
  const [step, setStep] = useState(1);
  const [data, setData] = useState<AppointmentData>(initialData);
  const [complete, setComplete] = useState(false);

  const selectedService = useMemo(
    () => services.find((service) => service.id === data.service),
    [data.service],
  );
  const selectedDay = useMemo(
    () => days.find((day) => day.id === data.day),
    [data.day],
  );

  const update = (key: keyof AppointmentData, value: string) => {
    setData((current) => ({ ...current, [key]: value }));
  };

  const canContinue =
    step === 1
      ? Boolean(data.name.trim() && data.phone.trim() && data.service)
      : step === 2
        ? Boolean(data.day && data.time)
        : true;

  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (step < 3) {
      if (canContinue) setStep((current) => current + 1);
      return;
    }
    setComplete(true);
  };

  if (complete) {
    return (
      <div className="appointment-success" role="status">
        <div className="success-icon">
          <Icon name="check" size={38} />
        </div>
        <span className="eyebrow">درخواست ثبت شد</span>
        <h2>سپاس، {data.name}</h2>
        <p>
          درخواست نمایشی شما برای {selectedDay?.label} ساعت {data.time} ثبت شد.
          در نسخه نهایی، همکاران مطب برای تأیید قطعی با شما تماس می‌گیرند.
        </p>
        <div className="success-summary">
          <span>
            <small>خدمت انتخابی</small>
            {selectedService?.title}
          </span>
          <span>
            <small>شماره تماس</small>
            <bdi>{data.phone}</bdi>
          </span>
        </div>
        <button
          type="button"
          className="button button-outline"
          onClick={() => {
            setData(initialData);
            setStep(1);
            setComplete(false);
          }}
        >
          ثبت درخواست جدید
        </button>
      </div>
    );
  }

  return (
    <div className="appointment-shell">
      <div className="appointment-progress">
        {[1, 2, 3].map((item) => (
          <div
            key={item}
            className={`${item === step ? "active" : ""} ${item < step ? "done" : ""}`}
          >
            <span>
              {item < step ? (
                <Icon name="check" size={15} />
              ) : (
                ["۰۱", "۰۲", "۰۳"][item - 1]
              )}
            </span>
            <small>
              {item === 1 ? "اطلاعات اولیه" : item === 2 ? "زمان مراجعه" : "بازبینی"}
            </small>
          </div>
        ))}
        <i style={{ "--progress": `${((step - 1) / 2) * 100}%` } as React.CSSProperties} />
      </div>

      <form onSubmit={submit} className="appointment-form">
        {step === 1 && (
          <div className="form-step">
            <div className="form-step-head">
              <span>مرحله ۱ از ۳</span>
              <h2>از شما بیشتر بدانیم</h2>
              <p>اطلاعات زیر برای هماهنگی اولیه نوبت استفاده می‌شود.</p>
            </div>
            <div className="form-row">
              <label>
                <span>نام و نام خانوادگی *</span>
                <input
                  required
                  autoFocus
                  autoComplete="name"
                  value={data.name}
                  onChange={(event) => update("name", event.target.value)}
                  placeholder="نام کامل شما"
                />
              </label>
              <label>
                <span>شماره موبایل *</span>
                <input
                  required
                  dir="ltr"
                  inputMode="tel"
                  autoComplete="tel"
                  value={data.phone}
                  onChange={(event) => update("phone", event.target.value)}
                  placeholder="09xxxxxxxxx"
                />
              </label>
            </div>
            <div className="form-row">
              <label>
                <span>سن</span>
                <input
                  inputMode="numeric"
                  value={data.age}
                  onChange={(event) => update("age", event.target.value)}
                  placeholder="مثلاً ۲۹"
                />
              </label>
              <label>
                <span>خدمت موردنظر *</span>
                <select
                  required
                  value={data.service}
                  onChange={(event) => update("service", event.target.value)}
                >
                  <option value="">انتخاب کنید</option>
                  {services.map((service) => (
                    <option value={service.id} key={service.id}>
                      {service.title}
                    </option>
                  ))}
                </select>
              </label>
            </div>
            <label>
              <span>سابقه جراحی بینی دارید؟</span>
              <select
                value={data.history}
                onChange={(event) => update("history", event.target.value)}
              >
                <option value="">انتخاب کنید</option>
                <option value="no">خیر</option>
                <option value="yes-once">بله، یک‌بار</option>
                <option value="yes-more">بله، بیش از یک‌بار</option>
              </select>
            </label>
          </div>
        )}

        {step === 2 && (
          <div className="form-step">
            <div className="form-step-head">
              <span>مرحله ۲ از ۳</span>
              <h2>زمان ترجیحی را انتخاب کنید</h2>
              <p>زمان انتخاب‌شده پس از تماس مطب و بررسی ظرفیت، قطعی می‌شود.</p>
            </div>
            <fieldset>
              <legend>روز مراجعه *</legend>
              <div className="date-options">
                {days.map((day) => (
                  <label key={day.id} className={data.day === day.id ? "selected" : ""}>
                    <input
                      type="radio"
                      name="day"
                      value={day.id}
                      checked={data.day === day.id}
                      onChange={() => update("day", day.id)}
                    />
                    <strong>{day.label}</strong>
                    <small>{day.detail}</small>
                  </label>
                ))}
              </div>
            </fieldset>
            <fieldset>
              <legend>ساعت ترجیحی *</legend>
              <div className="time-options">
                {times.map((time) => (
                  <label key={time} className={data.time === time ? "selected" : ""}>
                    <input
                      type="radio"
                      name="time"
                      value={time}
                      checked={data.time === time}
                      onChange={() => update("time", time)}
                    />
                    {time}
                  </label>
                ))}
              </div>
            </fieldset>
            <label>
              <span>توضیحات تکمیلی</span>
              <textarea
                rows={4}
                value={data.notes}
                onChange={(event) => update("notes", event.target.value)}
                placeholder="اگر توضیح یا درخواست خاصی دارید، اینجا بنویسید..."
              />
            </label>
          </div>
        )}

        {step === 3 && (
          <div className="form-step">
            <div className="form-step-head">
              <span>مرحله ۳ از ۳</span>
              <h2>بازبینی درخواست</h2>
              <p>پیش از ثبت، اطلاعات زیر را بررسی کنید.</p>
            </div>
            <div className="review-card">
              <div>
                <small>نام مراجعه‌کننده</small>
                <strong>{data.name}</strong>
              </div>
              <div>
                <small>شماره تماس</small>
                <strong dir="ltr">{data.phone}</strong>
              </div>
              <div>
                <small>خدمت موردنظر</small>
                <strong>{selectedService?.title}</strong>
              </div>
              <div>
                <small>زمان ترجیحی</small>
                <strong>
                  {selectedDay?.label}، ساعت {data.time}
                </strong>
              </div>
            </div>
            {data.notes && (
              <div className="review-notes">
                <small>توضیحات شما</small>
                <p>{data.notes}</p>
              </div>
            )}
            <label className="form-consent">
              <input type="checkbox" required />
              <span>
                تأیید می‌کنم اطلاعات واردشده صحیح است و می‌پذیرم مطب برای
                هماهنگی با من تماس بگیرد.
              </span>
            </label>
            <div className="frontend-notice">
              این فرم در حال حاضر نمایشی است و هنوز به سامانه نوبت‌دهی متصل نشده
              است.
            </div>
          </div>
        )}

        <div className="form-navigation">
          {step > 1 && (
            <button
              type="button"
              className="button button-outline"
              onClick={() => setStep((current) => current - 1)}
            >
              مرحله قبل
            </button>
          )}
          <button
            type="submit"
            className="button button-primary button-large"
            disabled={!canContinue}
          >
            {step === 3 ? "ثبت درخواست نوبت" : "ادامه"}
            <Icon name={step === 3 ? "calendar" : "arrow"} size={18} />
          </button>
        </div>
      </form>
    </div>
  );
}
