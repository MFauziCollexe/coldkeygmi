<template>
  <GuestLayout>
    <div class="min-h-screen bg-gradient-to-br from-slate-950 to-slate-900 py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <PageHeader title="Privacy Policy" />

        <!-- Content -->
        <ContentContainer>
          <!-- Introduction -->
          <LegalSection number="Introduction" title="" :divider="true">
            <p>Golden Multi Indotama ("we," "us," "our," or "Company") operates the ColdKey application. This page informs you of our policies regarding the collection, use, and disclosure of personal data when you use our Service and the choices you have associated with that data.</p>
          </LegalSection>

          <!-- 1. Information Collection and Use -->
          <LegalSection number="1" title="Information Collection and Use" :divider="true">
            <p>We collect several different types of information for various purposes to provide and improve our Service to you.</p>
            
            <h3 class="text-lg font-semibold text-white mt-4 mb-2">Types of Data Collected:</h3>
            <ul class="list-disc list-inside space-y-2 mt-3">
              <li><strong>Personal Data:</strong> While using our Service, we may ask you to provide us with certain personally identifiable information that can be used to contact or identify you ("Personal Data"). This may include, but is not limited to:
                <ul class="list-circle list-inside ml-4 mt-2 space-y-1">
                  <li>Email address</li>
                  <li>First name and last name</li>
                  <li>Department and job position</li>
                  <li>Work location or position</li>
                  <li>Cookies and Usage Data</li>
                </ul>
              </li>
              <li><strong>Usage Data:</strong> We may also collect information on how the Service is accessed and used ("Usage Data"). This may include information such as your computer's Internet Protocol address, browser type, browser version, pages visited, time and date of visit, and other diagnostic data.</li>
              <li><strong>Cookies Data:</strong> We use cookies and similar tracking technologies to track activity on our Service and store certain information.</li>
            </ul>
          </LegalSection>

          <!-- 2. Use of Data -->
          <LegalSection number="2" title="Use of Data" :divider="true">
            <p>Golden Multi Indotama uses the collected data for various purposes:</p>
            <ul class="list-disc list-inside mt-3 space-y-2">
              <li>To provide and maintain our Service</li>
              <li>To notify you about changes to our Service</li>
              <li>To allow you to participate in interactive features of our Service when you choose to do so</li>
              <li>To provide customer support</li>
              <li>To gather analysis or valuable information so that we can improve our Service</li>
              <li>To monitor the usage of our Service</li>
              <li>To detect, prevent and address technical issues</li>
            </ul>
          </LegalSection>

          <!-- 3. Security of Data -->
          <LegalSection number="3" title="Security of Data" :divider="true">
            <p>The security of your data is important to us, but remember that no method of transmission over the Internet or method of electronic storage is 100% secure. While we strive to use commercially acceptable means to protect your Personal Data, we cannot guarantee its absolute security.</p>
          </LegalSection>

          <!-- 4. Changes to This Privacy Policy -->
          <LegalSection number="4" title="Changes to This Privacy Policy" :divider="true">
            <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last updated" date at the top of this Privacy Policy.</p>
            <p class="mt-3">You are advised to review this Privacy Policy periodically for any changes. Changes to this Privacy Policy are effective when they are posted on this page.</p>
          </LegalSection>

          <!-- 5. User Rights -->
          <LegalSection number="5" title="User Rights" :divider="true">
            <p>Depending on your location, you may have certain rights regarding your personal data, including:</p>
            <ul class="list-disc list-inside mt-3 space-y-2">
              <li>The right to access your personal data</li>
              <li>The right to request correction of inaccurate data</li>
              <li>The right to request deletion of your data</li>
              <li>The right to opt-out of data processing</li>
              <li>The right to data portability</li>
            </ul>
          </LegalSection>

          <!-- 6. Third-Party Services -->
          <LegalSection number="6" title="Third-Party Services" :divider="true">
            <p>Our Service may contain links to other sites that are not operated by us. This Privacy Policy applies only to our Service, and we are not responsible for the privacy practices of other websites. We encourage you to review the Privacy Policy of any third-party site before providing your personal information.</p>
          </LegalSection>

          <!-- 7. Contact Us -->
          <LegalSection number="7" title="Contact Us" :divider="true">
            <h2 class="text-2xl font-bold text-white mb-4">7. Contact Us</h2>
            <p>If you have any questions about this Privacy Policy, please contact us at:</p>
            <div class="mt-3 text-indigo-400">
              <p>Golden Multi Indotama</p>
              <p>Email: privacy@goldenmulti.com</p>
              <p>Address: Jakarta, Indonesia</p>
            </div>
          </LegalSection>

          <!-- 8. Data Retention -->
          <LegalSection number="8" title="Data Retention" :divider="true">
            <h2 class="text-2xl font-bold text-white mb-4">8. Data Retention</h2>
            <p>We will retain your Personal Data only for as long as is necessary for the purposes set out in this Privacy Policy. We will retain and use your Personal Data to the extent necessary to comply with our legal obligations.</p>
          </LegalSection>

          <!-- 9. Your Acceptance of These Terms -->
          <LegalSection number="9" title="Your Acceptance of These Terms" :divider="false">
            <h2 class="text-xl font-bold text-white mb-3">9. Your Acceptance of These Terms</h2>
            <p>By using this Service, you signify your acceptance of this policy. If you do not agree to this policy, please do not use our Service. Your continued use of the Service following the posting of revised Privacy Policy means that you accept and agree to the changes.</p>
          </LegalSection>
        </ContentContainer>
      </div>
    </div>
  </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ContentContainer from '@/Components/ContentContainer.vue';
import LegalSection from '@/Components/LegalSection.vue';
</script>
